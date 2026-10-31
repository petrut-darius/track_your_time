import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, throwError } from 'rxjs';
import { LeaderboardResponse } from '../models/leaderboard-response';

@Injectable({
  providedIn: 'root',
})
export class Leaderboard {
  private http = inject(HttpClient);

  loadLeaderboard(id: string): Observable<LeaderboardResponse[]> {
    return this.http.get<{data: LeaderboardResponse[]}>(`/api/leaderboard/${id}`, {withCredentials: true}).pipe(
      map((response) => response.data),
      catchError((error) => {
        return throwError(() => error);
      })
    )
  }

  loadFriendsLeaderboard(id: string): Observable<LeaderboardResponse[]> {
    return this.http.get<{data: LeaderboardResponse[]}>(`/api/friends-leaderboard/${id}`, {withCredentials: true}).pipe(
      map((response) => response.data),
      catchError((error) => {
        return throwError(() => error);
      })
    )
  }
}
