import { Component, OnInit } from '@angular/core';
import { Auth } from '../../services/auth';
import { LoginRequest } from '../../models/login-request';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-login',
  imports: [FormsModule, CommonModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login implements OnInit {
  constructor(private authService: Auth) {

  }

  credentials: LoginRequest = {
    email: "",
    password: "",
  }

  isLoggedIn() {
    return this.authService.isLoggedIn();
  }

  login() {
    this.authService.login(this.credentials).subscribe(() => {
      console.log("Login Succesfully");
    });
  }

  ngOnInit(): void {
    this.isLoggedIn();
  }
}
