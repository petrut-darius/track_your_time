import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, throwError } from 'rxjs';
import { TypeResponse } from '../models/type-response';

@Injectable({
  providedIn: 'root',
})
export class Types {
  private http = inject(HttpClient);

  getAll(): Observable<TypeResponse[]> {
    return this.http.get<{data: TypeResponse[]}>("/api/types", { withCredentials: true }).pipe(
      map(response => response.data),
      catchError((error) => {
        return throwError(() => error);
      })
    )
  }
}
