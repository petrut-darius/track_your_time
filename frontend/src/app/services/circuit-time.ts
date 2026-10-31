import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, tap, throwError } from 'rxjs';
import { CircuitTimeRequest } from '../models/circuit-time-request';
import { CarResponse } from '../models/car-response';

@Injectable({
  providedIn: 'root',
})
export class CircuitTime {
  private http = inject(HttpClient);

  createCircuitTime(credentials: CircuitTimeRequest): Observable<CarResponse> {
    console.log(credentials);
    return this.http.post<{data: CarResponse}>(`/api/circuit/${credentials.circuit}/time/create`, credentials, {withCredentials: true}).pipe(
      map((response) => response.data),
      catchError(error => {
        console.error("Server error:", error);

        return throwError(() => error);
      }),
    )
  }
}
