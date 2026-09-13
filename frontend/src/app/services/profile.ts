import { Injectable } from '@angular/core';
import { BehaviorSubject, catchError, map, Observable, of, tap, throwError } from 'rxjs';
import { UserData } from '../models/user-data';
import { HttpClient } from '@angular/common/http';
import { ProfileEditRequest } from '../models/profile-edit-request';
import { ProfileEditResponse } from '../models/profile-edit-response';

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

  updateProfile(credentials: ProfileEditRequest): Observable<ProfileEditResponse> {
    const hasAvatar = credentials.avatar instanceof File;

    const body: FormData | Omit<ProfileEditRequest, 'avatar'> = hasAvatar
        ? this.toFormData(credentials)
        : {
          email: credentials.email,
          username: credentials.username,
          first_name: credentials.first_name,
          last_name: credentials.last_name,
        };

    return this.http.patch<{data: ProfileEditResponse}>("/api/profile", body, {withCredentials: true}).pipe(
      map(response => response.data),
      catchError((error) => {
        return throwError(() => error);
      })
    );
  }

  private toFormData(credentials: ProfileEditRequest): FormData {
    const formData = new FormData();
    
    formData.append("email", credentials.email);
    formData.append("username", credentials.username);
    formData.append("first_name", credentials.first_name);
    formData.append("last_name", credentials.last_name);

    if(credentials.avatar) {
      formData.append("avatar", credentials.avatar);
    }

    return formData;
  }
}
