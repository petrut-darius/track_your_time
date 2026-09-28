import { Component, inject } from '@angular/core';
import { Auth } from '../../services/auth';
import { Router } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule],
  templateUrl: './register.html',
  styleUrl: './register.css',
})
export class Register {
  private authService = inject(Auth);
  private router = inject(Router);

  private formBuilder = inject(FormBuilder);

  registerForm: FormGroup = this.formBuilder.group({
    username: ["", [Validators.required, Validators.minLength(4), Validators.maxLength(30)]],
    email: ["", [Validators.email, Validators.required]],
    last_name: ["", [Validators.required, Validators.maxLength(30)]],
    first_name: ["", [Validators.required, Validators.maxLength(30)]],
    password: ["", [Validators.required, Validators.minLength(8)]],
  })

  errorMessage: string|null = null;

  register() {
    this.errorMessage = null;

    if(this.registerForm.invalid) {
      this.registerForm.markAllAsTouched();
      this.errorMessage = "Please fill in all required fields.";
      return;
    }

    this.authService.register(this.registerForm.getRawValue()).subscribe({
      next: () => {
        this.router.navigate(["/profile/edit"]);
        console.log("Registered and logged in successfully");
      },
      error: (err: HttpErrorResponse) => {

        if(err.status === 422 && err.error?.data) {
          this.applyServerValidationErrors(err.error.data);
        }
      }
    })
  }

  private applyServerValidationErrors(violations: Record<string, string>) {
    for(const [field, message] of Object.entries(violations)) {
      const control = this.registerForm.get(this.toCamelCase(field));
      control?.setErrors({ server: message});
    }
  }

  private toCamelCase(snake: string): string {
    return snake.replace(/_([a-z])/g, (_, c) => c.toUpperCase());
  }
}
