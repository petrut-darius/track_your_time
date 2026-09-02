import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { LoginRequest } from '../models/login-request';
import { catchError, map, Observable, tap, throwError } from 'rxjs';
import { LoginResponse } from '../models/login-response';

@Injectable({
  providedIn: 'root',
})
export class Auth {
  constructor(private httpclient: HttpClient) {

  }

  login(credentials: LoginRequest) : Observable<LoginResponse>{
    return this.httpclient.post<LoginResponse>("https://track-your-time.ddev.site/api/login_check", credentials)
      .pipe(tap((response: LoginResponse) => {
        localStorage.setItem("accessToken", response.token);
        return response;
      }),
      catchError((error) => {
        return throwError(() => error);
      })
    );
  }

  refreshToken() : Observable<LoginResponse> {
    return this.httpclient.post<LoginResponse>("https://track-your-time.ddev.site/api/token/refresh", { }, { withCredentials: true })
      .pipe(tap((response: LoginResponse) => {
        localStorage.setItem("accessToken", response.token);

        return response;
      }));
  }

  logout() {
    localStorage.removeItem("accessToken");
  }

  isLoggedIn() : boolean {
    return localStorage.getItem("accessToken") !== null;
  }

  getAccessToken(): string | null {
    return localStorage.getItem("accessToken");
  }
}
