import { Component, inject, OnInit, signal } from '@angular/core';
import { Car } from '../../services/car';
import { ActivatedRoute, Router } from '@angular/router';
import { AbstractControl, FormBuilder, FormGroup, ReactiveFormsModule, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';
import { BubbleMenu, Editor, StarterKit } from '@domternal/core';
import { DomternalEditorComponent, DomternalToolbarComponent } from '@domternal/angular';
import { CarResponse } from '../../models/car-response';
import { HttpErrorResponse } from '@angular/common/http';
import { map, Observable, switchMap } from 'rxjs';

@Component({
  selector: 'app-car-edit',
  imports: [ReactiveFormsModule, DomternalEditorComponent, DomternalToolbarComponent],
  templateUrl: './car-edit.html',
  styleUrl: './car-edit.css',
})
export class CarEdit implements OnInit{
  private carService = inject(Car);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private initialValue: Record<string, any> = {};

  errorMessage: string | null = null;

  private formBuilder = inject(FormBuilder);
  storyEditor = signal<Editor | null>(null);
  extensions = [StarterKit, BubbleMenu];

  private carId: string | null = null;

  ngOnInit(): void {
    this.carId = this.route.snapshot.paramMap.get("id");

    if(this.carId) {
      this.carService.getCarById(this.carId).subscribe({
        next: (car) => {
          this.carForm.patchValue({
            id: car.id,
            name: car.name,
            hp: car.hp,
            story: car.story,
          });

          this.initialValue = this.carForm.getRawValue();
        },
        error: () => {
          this.errorMessage = "Couldn't load your car.";
        }
      });

      Object.keys(this.carForm.controls).forEach(field => {
        this.carForm.get(field)?.valueChanges.subscribe(() => this.clearServerError(field));
      })
    }
  }

  carPhotos: File[] = [];

  carForm: FormGroup = this.formBuilder.group({
    name: ["", [Validators.required, Validators.minLength(4)]],
    hp: [null as number | null, [Validators.required]],
    story: ["", [this.richTextLength(50, 2000)]]
  });

  onPhotosSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    this.carPhotos = input.files ? Array.from(input.files) : [];
  }

  updateCar() {
    this.errorMessage = null;

    if(this.carForm.invalid) {
      this.carForm.markAllAsTouched();
      this.errorMessage = "Please fill in all required fields.";
      return;
    }

    const changed = this.getChangedFields();

    if(Object.keys(changed).length === 0 && this.carPhotos.length === 0) {
      this.errorMessage = "You need to update the data:)))";
      return;
    }

    const data = changed ? changed : this.carForm.getRawValue();

    const payload = {
      ...data,
      ...(this.carPhotos.length > 0 ? { photos: this.carPhotos} : {}),
    }

    if(!this.carId) return;

    this.carService.updateCar(this.carId!, payload).subscribe({
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

  private applyServerValidationErrors(violations: Record<string, string>) {
    for(const [field, message] of Object.entries(violations)) {
      const control = this.carForm.get(this.toCamelCase(field));
      control?.setErrors({ server: message});
    }
  }

  private toCamelCase(snake: string): string {
    return snake.replace(/_([a-z])/g, (_, c) => c.toUpperCase());
  }

  private clearServerError(field: string) {
    const control = this.carForm.get(field);
    if(control?.errors?.["server"]) {
      const { server, ...rest} = control.errors;
      control.setErrors(Object.keys(rest).length ? rest : null);
    }
  }

  private getChangedFields(): Record<string, any> {
    const current = this.carForm.getRawValue();
    const changed: Record<string, any> = {};

    for(const key of Object.keys(current)) {
      if(current[key] !== this.initialValue[key]) {
        changed[key] = current[key];
      }
    }

    return changed;
  }
}
