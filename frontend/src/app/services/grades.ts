import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, throwError } from 'rxjs';
import { GradeResponse } from '../models/grade-response';

@Injectable({
  providedIn: 'root',
})
export class Grades {
  private http = inject(HttpClient);

  getAll(): Observable<GradeResponse[]> {
    return this.http.get<{data: GradeResponse[]}>("/api/grades", { withCredentials: true }).pipe(
      map(response => response.data),
      catchError((error) => {
        return throwError(() => error);
      })
    )
  }
}
