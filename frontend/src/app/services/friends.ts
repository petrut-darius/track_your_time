import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, tap, throwError } from 'rxjs';
import { FriendData } from '../models/friend-data';
import { AddFriendResponse } from '../models/add-friend-response';

@Injectable({
  providedIn: 'root',
})
export class Friends {
  private http = inject(HttpClient);

  getFriends(): Observable<FriendData[]> {
    return this.http.get<{data: FriendData[]}>("/api/friends", { withCredentials: true }).pipe(
      map((response) => response.data),
      catchError((error) => {
        return throwError(() => error);
      }),
    );
  }

  getFriendRequests(): Observable<FriendData[]> {
    return this.http.get<{data: FriendData[]}>("/api/friend-requests", { withCredentials: true}).pipe(
      map((response) => response.data),
      catchError((error) => {
        return throwError(() => error);
      })
    );
  }

  addFriend(id: string): Observable<AddFriendResponse> {
    return this.http.post<{data: AddFriendResponse}>(`/api/add-friend/${id}`, {}, { withCredentials: true}).pipe(
      map((response) => response.data),
      catchError((error) => {
        return throwError(() => error);
      }),
    )
  }

  acceptFriendRequest(id: number): Observable<AddFriendResponse> {
    return this.http.patch<{data: AddFriendResponse}>(`/api/update-friend/${id}`, {}, { withCredentials: true}).pipe(
      map((response) => response.data),
      catchError((error) => {
        return throwError(() => error);
      })
    );
  }

  removeFriendRequest(id: number): Observable<AddFriendResponse> {
    return this.http.delete<{data: AddFriendResponse}>(`/api/remove-friend/${id}`, { withCredentials: true }).pipe(
        map(reponse => reponse.data),
        catchError((error) => {
          return throwError(() => error);
        })
    );
  }
}
