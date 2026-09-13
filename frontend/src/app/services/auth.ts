import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { LoginRequest } from '../models/login-request';
import { BehaviorSubject, catchError, map, Observable, of, tap, throwError } from 'rxjs';
import { LoginResponse } from '../models/login-response';
import { UserData } from '../models/user-data';

@Injectable({
  providedIn: 'root',
})
export class Auth {
  //also create a subject that keeps the user data that should be updated on the login page, register, and authCheck

  private isAuthenticated$ = new BehaviorSubject<boolean>(false); //subject -> e un observable care poate fi multicasted to many observers
  readonly authStatus$ = this.isAuthenticated$.asObservable();

  private userInfo$ = new BehaviorSubject<UserData | null>(null);
  readonly user$ = this.userInfo$.asObservable();

  constructor(private http: HttpClient) {}

  login(credentials: LoginRequest): Observable<UserData> {
    return this.http.post<{data: UserData}>("/api/login_check", credentials, { withCredentials: true})
                      .pipe(map(response => response.data),
                            tap((userData) => {
                              this.userInfo$.next(userData);
                              this.isAuthenticated$.next(true);
                            }),// .next trimite valoarea(true) catre observer; .tap() -> daca vrei sa trimiti un logger spre exemplu, ca nu poti schimba valoarea primita in el
                            catchError((error) => {
                              this.userInfo$.next(null);
                              this.isAuthenticated$.next(false);
                              return throwError(() => error);
                            }));
  }

  refreshToken(): Observable<void> {
    return this.http.post("/api/token/refresh", {}, { withCredentials: true})
                      .pipe(map(() => void 0),
                              catchError((error) => {
                                this.userInfo$.next(null);
                                this.isAuthenticated$.next(false);
                                return throwError(() => error);
                              }));
  }

  logout(): Observable<void> {
    // needs a real backend endpoint that clears the cookie server-side —
    // JS cannot delete an httpOnly cookie itself
    return this.http.post<void>("/api/logout", {}, { withCredentials: true })
      .pipe(tap(() => this.isAuthenticated$.next(false)));
  }

  // call this once at app startup (APP_INITIALIZER), not on every route check
  checkAuthStatus(): Observable<boolean> {
    return this.http.get<{data: UserData}>("/api/me", { withCredentials: true }).pipe(
      tap((response) => {
        this.userInfo$.next(response.data);
        this.isAuthenticated$.next(true);
      }),
      map(() => {
        return true;
      }),
      catchError(() => {
        this.isAuthenticated$.next(false);
        return of(false);
      })
    );
  }
}
