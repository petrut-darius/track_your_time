import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, OnInit, Signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { catchError, map, of, shareReplay, switchMap, tap, throwError } from 'rxjs';
import { Circuit } from '../../services/circuit';
import { AsyncPipe, TitleCasePipe } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Car } from '../../services/car';
import { CircuitTime } from '../../services/circuit-time';
import { CircuitTimeRequest } from '../../models/circuit-time-request';
import { Leaderboard } from '../../services/leaderboard';
import { Auth } from '../../services/auth';

@Component({
  selector: 'app-circuits-show',
  imports: [AsyncPipe, TitleCasePipe, RouterLink, ReactiveFormsModule],
  templateUrl: './circuits-show.html',
  styleUrl: './circuits-show.css',
})
export class CircuitsShow{
  private route = inject(ActivatedRoute);
  private circuitService = inject(Circuit);
  private fb = inject(FormBuilder);
  private carService = inject(Car);
  private circuitTimeService = inject(CircuitTime);
  private router = inject(Router);
  private leaderboardService = inject(Leaderboard);

  errorMessage = <string | null>(null);

  private circuitId: string | null = this.route.snapshot.paramMap.get("id");

  cars$ = this.carService.getCurrentUserCars().pipe(
    catchError((err: HttpErrorResponse) => {
      console.log("Failed to load user cars", err.status, err.message);
      return of(null);
    }),
    tap(response => console.log(response)),
  );

  friendsLeaderboard$ = this.leaderboardService.loadFriendsLeaderboard(this.circuitId!).pipe(
    catchError((err: HttpErrorResponse) => {
      console.log("Failed to load user friends leaderboard", err.status, err.message);
      return of(null);
    }),
    tap(response => console.log(response)),
  )

  leaderboard$ = this.leaderboardService.loadLeaderboard(this.circuitId!).pipe(
    catchError((err: HttpErrorResponse) => {
      console.log("Failed to load user leaderboard", err.status, err.message);
      return of(null);
    }),
    tap(response => console.log(response)),
  )

  circuitTimeForm = this.fb.nonNullable.group({
    minutes: [0, [Validators.required, Validators.min(0), Validators.max(20)]],
    seconds: [0, [Validators.required, Validators.min(0), Validators.max(59)]],
    car: ["", [Validators.required, Validators.min(0)]],
  });

  protected scrapedSite = "https://www.racingcircuits.info/";

  circuit$ = this.route.paramMap.pipe(
    map(params => params.get("id")),
    switchMap(id => this.circuitService.getCircuit(id!).pipe(
      catchError((err: HttpErrorResponse) => {
        console.error("Failed to load circuit", err.status, err.message);
        return of(null);
      }),
      tap(response => console.log(response)),
    )),
    shareReplay(1),
  )

  createCircuitTime() {
    const circuit = this.circuitId;

    this.errorMessage = null;

    if(this.circuitTimeForm.invalid) {
      this.circuitTimeForm.markAllAsTouched();
      this.errorMessage = "Please fill in all required fields.";
      return;
    }

    const {minutes, seconds, car} = this.circuitTimeForm.getRawValue();

    if(car === null) return;

    if(!circuit) return;

    const request: CircuitTimeRequest = { circuit ,minutes, seconds, car};

    this.circuitTimeService.createCircuitTime(request).subscribe({
      next: (car) => {
        this.router.navigate(["/cars", car.id]);
      },
      error: ((err: HttpErrorResponse) => {
        if(err.status === 422 && err.error?.data) {
          this.applyServerValidationErrors(err.error.data);
        }else if(err.status === 401) {
          this.errorMessage = "Your session expired. Please log in again.";
        } else {
          this.errorMessage = "Something went wrong updating your profile.";
        }
      }),
    })
  }

  private applyServerValidationErrors(violations: Record<string, string>) {
    for(const [field, message] of Object.entries(violations)) {
      const control = this.circuitTimeForm.get(this.toCamelCase(field));
      control?.setErrors({ server: message});
    }
  }

  private toCamelCase(snake: string): string {
    return snake.replace(/_([a-z])/g, (_, c) => c.toUpperCase());
  }
}
