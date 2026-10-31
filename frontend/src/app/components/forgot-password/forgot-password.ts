import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { PasswordReset } from '../../services/password-reset';

@Component({
  selector: 'app-forgot-password',
  imports: [ReactiveFormsModule],
  templateUrl: './forgot-password.html',
  styleUrl: './forgot-password.css',
})
export class ForgotPassword {
  private fb = inject(FormBuilder);
  private passowrdReset = inject(PasswordReset);

  errorMessage: string | null = null;
  requestSent: boolean | null = null;

  forgotPasswordForm = this.fb.group({
    email: ["", [Validators.required, Validators.email]]
  });

  forgotPassword() {
    this.errorMessage = null;

    if(this.forgotPasswordForm.invalid) {
      this.forgotPasswordForm.markAllAsTouched();
      this.errorMessage = "The email address must be valid.";
      return;
    }

    const { email }= this.forgotPasswordForm.getRawValue();

  this.passowrdReset.forgotPassword(email!).subscribe({
    next: () => this.requestSent = true,
    error: () => this.errorMessage = 'Could not send the request. Please try again.',
  });
  }
}
