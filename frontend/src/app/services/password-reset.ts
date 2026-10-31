import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, of, throwError } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class PasswordReset {
  private http = inject(HttpClient);

  resetPassword(token: string, password: string) {
    return this.http.post("/api/reset-password/reset", {token, password}, {withCredentials: true}).pipe(
      catchError(err => {
        return throwError(() => err);
      })
    )
  }

  forgotPassword(email: string) {
    return this.http.post<{data: { message: string }}>("/api/reset-password/request", { email }, {withCredentials: true});
  }
}
