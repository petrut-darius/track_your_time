import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, throwError } from 'rxjs';
import { CircuitResponse } from '../models/circuit-response';
import { PaginatedResponse } from '../models/paginated-response';

@Injectable({
  providedIn: 'root',
})
export class Circuit {
  private http = inject(HttpClient);

  getCircuits(page: number = 1, limit: number = 10, search: string = ""): Observable<PaginatedResponse<CircuitResponse>> {
    let params = new HttpParams()
                      .set("page", page.toString())
                      .set("limit", limit.toString());
    
    if(search.trim()) {
      params = params.set("q", search.trim());
    }

    return this.http.get<PaginatedResponse<CircuitResponse>>("/api/circuits", { params, withCredentials: true });
  }

  getCircuit(id: string): Observable<CircuitResponse> {
    return this.http.get<{data: CircuitResponse}>(`/api/circuits/${id}`, {withCredentials: true}).pipe(
      map(response => response.data),
      catchError(error => {
        return throwError(() => error);
      })
    );
  }
}
