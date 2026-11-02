import { Component, inject, signal } from '@angular/core';
import { AsyncPipe, CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Profile } from '../../services/profile';
import { catchError, combineLatest, map, of, shareReplay, switchMap } from 'rxjs';
import { Auth } from '../../services/auth';
import { HttpErrorResponse } from '@angular/common/http';
import { Friends } from '../../services/friends';
import { AddFriendResponse } from '../../models/add-friend-response';
import { Car } from '../../services/car';
import { toSignal } from '@angular/core/rxjs-interop';

@Component({
  selector: 'app-profile-index',
  imports: [AsyncPipe, CommonModule, RouterLink],
  templateUrl: './profile-index.html',
  styleUrl: './profile-index.css',
})
export class ProfileIndex {
  private route = inject(ActivatedRoute);
  private profileService = inject(Profile);
  private authService = inject(Auth);
  private friendsService = inject(Friends);
  private carService = inject(Car);
  loading = signal(false);
  data = signal<AddFriendResponse | null>(null);
  error = signal<string| null>(null);

  //isOwnProfile = toSignal(this.isOwnProfile$, {initialValue: false});

  protected readonly enviroment = "/uploads/";

  user$ = this.route.paramMap.pipe(
    map(params => params.get("id")),
    switchMap(id => this.profileService.getProfileById(id!).pipe(
      catchError((err: HttpErrorResponse) => {
        console.error('Failed to load profile', err.status, err.message);
        return of(null);
      }),
    )),
    shareReplay(1),
  )

  isOwnProfile$ = combineLatest([this.user$, this.authService.user$]).pipe(
    map(([viewedUser, currentUser]) => 
      viewedUser !== null && currentUser !== null && viewedUser.id === currentUser.id
    ),
  )

  userCars$ = this.route.paramMap.pipe(
    map(params => params.get("id")),
    switchMap(id => this.carService.getUserCars(id!).pipe(
      catchError((err: HttpErrorResponse) => {
        console.error("Failed to load cars", err.status, err.message);
        return of(null);
      }),
    )),
    shareReplay(1),
  )

  addFriend(): void {
    const id = this.route.snapshot.paramMap.get("id");

    this.loading.set(true);
    this.error.set(null);

    this.friendsService.addFriend(id!).subscribe({
      next: (response) => {
        this.data.set(response);
        this.loading.set(false);
      },
      error: (err: HttpErrorResponse) => {
        if(err.status === 409) {
          this.data.set({message: "Friend request already pending."});
        } else {
          this.error.set("Could not send friend request. Try again.");
        }

        this.loading.set(false);
      }
    })
  }
}
