import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, throwError } from 'rxjs';
import { CarResponse } from '../models/car-response';
import { CarRequest } from '../models/car-request';

@Injectable({
  providedIn: 'root',
})
export class Car {
  private http = inject(HttpClient);

  createCar(credentials: CarRequest): Observable<CarResponse> {
    const hasPhotos = this.hasFiles(credentials.photos);

    const body: FormData | Omit<CarRequest, "photos"> = hasPhotos
      ? this.toFormData(credentials)
      : {
        name: credentials.name,
        story: credentials.story,
        hp: credentials.hp
      };

      if (body instanceof FormData) {
        for (const [key, value] of body.entries()) {
          console.log(key, value instanceof File ? `File(${value.name}, ${value.size}B)` : value);
        }
      }

    return this.http.post<{data: CarResponse}>("/api/cars/create", body, { withCredentials: true }).pipe(
      map((response) => response.data),
      catchError((error) => {
        return throwError(() => error);
      }),
    );
  }

  private hasFiles(photos?: File[] | FileList): boolean {
    if(!photos) return false;
    const list = photos instanceof File ? Array.from(photos) : photos;
    return Array.isArray(list) && list.length > 0 && list.every(f => f instanceof File);
  }

  private toFormData(credentials: CarRequest): FormData {
    const formData = new FormData();

    if (credentials.name !== undefined) formData.append("name", credentials.name);
    if (credentials.story !== undefined) formData.append("story", credentials.story);
    if (credentials.hp !== undefined) formData.append("hp", String(credentials.hp));

    const photos = credentials.photos instanceof FileList
      ? Array.from(credentials.photos)
      : (credentials.photos ?? []);

    photos.forEach(file => formData.append("photos[]", file, file.name));

    return formData;
  }

  getCarById(id: string): Observable<CarResponse> {
    return this.http.get<{data: CarResponse}>(`/api/cars/${id}`, { withCredentials: true }).pipe(
      map(response => response.data),
      catchError(error => {
        return throwError(() => error);
      })
    )
  }
}
