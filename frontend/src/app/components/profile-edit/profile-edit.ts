import { Component, OnInit, ViewChild } from '@angular/core';
import { FormsModule, NgForm } from '@angular/forms';
import { Profile } from '../../services/profile';
import { Router } from '@angular/router';
import { ProfileEditRequest } from '../../models/profile-edit-request';
import { Auth } from '../../services/auth';
import { take } from 'rxjs';

@Component({
  selector: 'app-profile-edit',
  imports: [FormsModule],
  templateUrl: './profile-edit.html',
  styleUrl: './profile-edit.css',
})
export class ProfileEdit implements OnInit {
  constructor(private profileEditService: Profile, private router: Router, private auth: Auth) {}

  profileForm: ProfileEditRequest = {
    email: "",
    username: "",
    last_name: "",
    first_name: "",
    avatar: null,
  }

  onAvatarSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    this.profileForm.avatar = input.files?.[0] ?? null;
  }

  errorMessage: string|null = null;

  @ViewChild("profileFormRef") profileFormRef!: NgForm;

  updateProfile() {
    this.errorMessage = null;

    if(this.profileFormRef.invalid) {
      this.errorMessage = "Please fill in all required fields.";
      return;
    }

    this.profileEditService.updateProfile(this.profileForm).subscribe({
      next: () => {
        this.auth.user$.pipe(take(1)).subscribe(user => {
          if(!user) {
            this.errorMessage = "Session expired.";
            return;
          }
          
          this.router.navigate(["/profile", user.id]);
          
          console.log("Profile updated successfully.");
        })
      },
      error: () => {
        this.errorMessage = "Invalid data sent";
      }
    })
  }

  ngOnInit(): void {
    this.auth.user$.pipe(take(1)).subscribe(user => {
      console.log('user in ProfileEdit ngOnInit:', user);
      if(!user) return;
      this.profileForm = {
        email: user.email,
        username: user.username,
        first_name: user.first_name,
        last_name: user.last_name,
        avatar: null,
      }
    })
  }
}
