import { Component, inject, OnInit, signal } from '@angular/core';
import { AbstractControl, FormBuilder, FormGroup, ReactiveFormsModule, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';
import {
  DomternalEditorComponent,
  DomternalToolbarComponent,
} from '@domternal/angular';
import { Editor, StarterKit, BubbleMenu } from '@domternal/core';
import { Car } from '../../services/car';
import { CarResponse } from '../../models/car-response';
import { Router } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-car-create',
  imports: [ReactiveFormsModule, DomternalEditorComponent, DomternalToolbarComponent],
  templateUrl: './car-create.html',
  styleUrl: './car-create.css',
})
export class CarCreate implements OnInit{
  private carService = inject(Car);
  private router = inject(Router);

  errorMessage: string | null = null;

  private formBuilder = inject(FormBuilder);
  storyEditor = signal<Editor | null>(null);
  extensions = [StarterKit, BubbleMenu];

  carPhotos: File[] = [];

  carForm: FormGroup = this.formBuilder.group({
    name: ["", [Validators.required, Validators.minLength(4)]],
    hp: [null as number | null, [Validators.required]],
    story: ["", [this.richTextLength(50, 2000)]]
  });

  richTextLength(min: number, max: number): ValidatorFn {
    return (control: AbstractControl): ValidationErrors | null => {
      const html = control.value;
      if (typeof html !== 'string') return { required: true };

      const text = (new DOMParser().parseFromString(html, 'text/html').body.textContent ?? '').trim();

      if (text.length === 0) return { required: true };
      if (text.length < min) return { minlength: { requiredLength: min, actualLength: text.length } };
      if (text.length > max) return { maxlength: { requiredLength: max, actualLength: text.length } };
      return null;
    };
  }

  ngOnInit(): void {
    
  }

  onPhotosSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    this.carPhotos = input.files ? Array.from(input.files) : [];
  }

  createCar() {
    this.errorMessage = null;

    if(this.carForm.invalid) {
      this.carForm.markAllAsTouched();
      this.errorMessage = "Please fill in all required fields.";
      return;
    }

    const payload = {
      ...this.carForm.getRawValue(),
      ...(this.carPhotos.length > 0 ? { photos: this.carPhotos} : {}),
    }

    this.carService.createCar(payload).subscribe({
      next: (car: CarResponse) => {
        this.router.navigate(["/cars", car.id]);
      },
      error: (err: HttpErrorResponse) => {
        if(err.status === 422 && err.error?.data) {
          this.applyServerValidationErrors(err.error.data);
        }else if(err.status === 401) {
          this.errorMessage = "Your session expired. Please log in again.";
        } else {
          this.errorMessage = "Something went wrong updating your profile.";
        }
      }
    });
  }

  private applyServerValidationErrors(violations: Record<string, string>) {
    for(const [field, message] of Object.entries(violations)) {
      const control = this.carForm.get(this.toCamelCase(field));
      control?.setErrors({ server: message});
    }
  }

  private toCamelCase(snake: string): string {
    return snake.replace(/_([a-z])/g, (_, c) => c.toUpperCase());
  }
}