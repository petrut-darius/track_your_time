import { Injectable } from '@angular/core';
import { BehaviorSubject, catchError, map, Observable, of, tap, throwError } from 'rxjs';
import { UserData } from '../models/user-data';
import { HttpClient } from '@angular/common/http';
import { ProfileEditRequest } from '../models/profile-edit-request';

@Injectable({
  providedIn: 'root',
})
export class Profile {
  private profileData$ = new BehaviorSubject<UserData | null>(null);
  readonly profileUser$ = this.profileData$.asObservable();

  constructor (private http: HttpClient) {}

  getProfileById(id: string): Observable<UserData> {
    return this.http.get<{data: UserData}>(`/api/user/${id}`, { withCredentials: true}).pipe(
      tap((response) => {
        this.profileData$.next(response.data);
      }),
      map((response) => response.data),
      catchError((error) => {
        this.profileData$.next(null);
        return throwError(() => error);
      })
    );
  }

  getMyProfile(): Observable<UserData> {
    return this.http.get<{data: UserData}>("/api/profile", {withCredentials: true}).pipe(
      map(response => response.data),
      catchError(error => {
        return throwError(() => error);
      })
    );
  }

  updateProfile(credentials: ProfileEditRequest): Observable<UserData> {
    const hasAvatar = credentials.avatar instanceof File;

    const body: FormData | Omit<ProfileEditRequest, 'avatar'> = hasAvatar
        ? this.toFormData(credentials)
        : {
          email: credentials.email,
          username: credentials.username,
          first_name: credentials.first_name,
          last_name: credentials.last_name,
        };

    return this.http.patch<{data: UserData}>("/api/profile", body, {withCredentials: true}).pipe(
      map(response => response.data),
      catchError((error) => {
        return throwError(() => error);
      })
    );
  }

  private toFormData(credentials: ProfileEditRequest): FormData {
    const formData = new FormData();

    if (credentials.email !== undefined) formData.append("email", credentials.email);
    if (credentials.username !== undefined) formData.append("username", credentials.username);
    if (credentials.first_name !== undefined) formData.append("first_name", credentials.first_name);
    if (credentials.last_name !== undefined) formData.append("last_name", credentials.last_name);
    if (credentials.avatar instanceof File) formData.append("avatar", credentials.avatar);

    return formData;
  }
}
