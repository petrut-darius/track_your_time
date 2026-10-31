import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { PasswordReset } from '../../services/password-reset';

@Component({
  selector: 'app-reset-password',
  imports: [ReactiveFormsModule],
  templateUrl: './reset-password.html',
  styleUrl: './reset-password.css',
})
export class ResetPassword {
  private route = inject(ActivatedRoute);
  private fb = inject(FormBuilder);
  private resetPasswordService = inject(PasswordReset);
  private router = inject(Router);

  errorMessage = <string | null>(null);

  private token: string | null = this.route.snapshot.queryParamMap.get("token");

  resetPasswordForm = this.fb.group({
    plainPassword: ["", [Validators.required, Validators.minLength(8), Validators.maxLength(255)]],
    confirmPassword: ['', Validators.required],
  });

  resetPassword() {
    this.errorMessage = null;

    if(!this.token) {
      this.errorMessage = "This reset link is missing its token. Request a new one.";
      return;
    }

    if(this.resetPasswordForm.invalid) {
      this.resetPasswordForm.markAllAsTouched();
      this.errorMessage = "Please fill in all required fields.";
      return;
    }

    const { plainPassword, confirmPassword } = this.resetPasswordForm.getRawValue();

    if(plainPassword !== confirmPassword) {
      this.errorMessage = "The passwords don't match.";
      return;
    }
    
    this.resetPasswordService.resetPassword(this.token, plainPassword!).subscribe({
      next: () => this.router.navigate(["/"]),
      error: () => this.errorMessage = "The reset link may be invalid or expired, or the password may not meet the requirements."
    })
  }
}
