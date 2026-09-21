import { Component, inject } from '@angular/core';
import { Auth } from '../../services/auth';
import { ActivatedRoute } from '@angular/router';
import { catchError, map, of, shareReplay, switchMap, tap } from 'rxjs';
import { Car } from '../../services/car';
import { HttpErrorResponse } from '@angular/common/http';
import { AsyncPipe } from '@angular/common';

@Component({
  selector: 'app-car-index',
  imports: [AsyncPipe],
  templateUrl: './car-index.html',
  styleUrl: './car-index.css',
})
export class CarIndex {
  private authService = inject(Auth);
  private route = inject(ActivatedRoute);
  private carService = inject(Car);

  protected readonly enviroment = "https://track-your-time.ddev.site/uploads/car/";

  car$ = this.route.paramMap.pipe(
    map(params => params.get("id")),
    switchMap(id => this.carService.getCarById(id!).pipe(
        catchError((err: HttpErrorResponse) => {
          console.error('Failed to load car', err.status, err.message);
          return of(null);
        }),
        tap(response => console.log(response)),
    )),
    shareReplay(1),
  );
}
