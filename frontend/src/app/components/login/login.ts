import { Component, OnInit } from '@angular/core';
import { Auth } from '../../services/auth';
import { LoginRequest } from '../../models/login-request';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';

@Component({
  selector: 'app-login',
  imports: [FormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login{
  constructor(private authService: Auth, private router: Router) {}

  credentials: LoginRequest = {
    email: "",
    password: "",
  }

  errorMessage: string|null = null;

  login() {
    this.authService.login(this.credentials).subscribe({
      next: () =>  {
        this.router.navigate(["/"]);
        console.log("Logged in successfully.")
      },
      error: () =>  {
        this.errorMessage = "Invalid email or password";
      }
    })
  }
}
