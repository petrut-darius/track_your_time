import { Component, inject, OnInit } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Profile } from '../../services/profile';
import { Router } from '@angular/router';
import { UserData } from '../../models/user-data';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-profile-edit',
  imports: [ReactiveFormsModule],
  templateUrl: './profile-edit.html',
  styleUrl: './profile-edit.css',
})
export class ProfileEdit implements OnInit {
  private fb = inject(FormBuilder);
  private profileEditService = inject(Profile);
  private router = inject(Router);
  private initialValue: Record<string, any> = {};

  errorMessage: string | null = null;
  avatarFile: File | null = null;

  profileForm: FormGroup = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    username: ['', [Validators.required, Validators.minLength(3)]],
    first_name: ['', Validators.required],
    last_name: ['', Validators.required],
  });

  ngOnInit(): void {
    this.profileEditService.getMyProfile().subscribe({
      next: (user) => {
        this.profileForm.patchValue({
          email: user.email,
          username: user.username,
          first_name: user.first_name,
          last_name: user.last_name,
        });

        this.initialValue = this.profileForm.getRawValue();
      },
      error: () => {
        this.errorMessage = "Couldn't load your profile.";
      }
    });

    Object.keys(this.profileForm.controls).forEach(field => {
      this.profileForm.get(field)?.valueChanges.subscribe(() => this.clearServerError(field));
    });
  }

  onAvatarSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    this.avatarFile = input.files?.[0] ?? null;
  }

  updateProfile() {
    this.errorMessage = null;

    if (this.profileForm.invalid) {
      this.profileForm.markAllAsTouched();
      this.errorMessage = "Please fill in all required fields.";
      return;
    }

    const changed = this.getChangedFields();

    if(Object.keys(changed).length === 0) {
      this.errorMessage = "You need to update the data:)))";
      return;
    }

    const payload = {
      ...changed,
      ...(this.avatarFile ? { avatar: this.avatarFile} : {}),
    };

    this.profileEditService.updateProfile(payload).subscribe({
      next: (updatedUser: UserData) => {
        this.router.navigate(["/profile", updatedUser.id]);
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
      const control = this.profileForm.get(this.toCamelCase(field));
      control?.setErrors({ server: message});
    }
  }

  private toCamelCase(snake: string): string {
    return snake.replace(/_([a-z])/g, (_, c) => c.toUpperCase());
  }

  private clearServerError(field: string) {
    const control = this.profileForm.get(field);
    if(control?.errors?.["server"]) {
      const { server, ...rest} = control.errors;
      control.setErrors(Object.keys(rest).length ? rest : null);
    }
  }

  private getChangedFields(): Record<string, any> {
    const current = this.profileForm.getRawValue();
    const changed: Record<string, any> = {};

    for(const key of Object.keys(current)) {
      if(current[key] !== this.initialValue[key]) {
        changed[key] = current[key];
      }
    }

    return changed;
  }
}