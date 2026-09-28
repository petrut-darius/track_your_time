import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, throwError } from 'rxjs';
import { CircuitResponse } from '../models/circuit-response';

@Injectable({
  providedIn: 'root',
})
export class Circuit {
  private http = inject(HttpClient);

  getCircuits(id: string): Observable<CircuitResponse> {
    return this.http.get<{data: CircuitResponse}>(`/api/circuits?p=${id}`, {withCredentials: true}).pipe(
      map(response => response.data),
      catchError((error) => {
        return throwError(() => error);
      })
    )
  }
}
