import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, throwError } from 'rxjs';
import { StatusResponse } from '../models/status-response';

@Injectable({
  providedIn: 'root',
})
export class Statuses {
  private http = inject(HttpClient);

  getAll(): Observable<StatusResponse[]> {
    return this.http.get<{data: StatusResponse[]}>("/api/statuses", { withCredentials: true }).pipe(
      map(response => response.data),
      catchError((error) => {
        return throwError(() => error);
      })
    )
  }
}
