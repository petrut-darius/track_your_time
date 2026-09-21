import { Component, inject, signal } from '@angular/core';
import { Auth } from '../../services/auth';
import { Friends as FriendsService } from '../../services/friends';
import { catchError, of } from 'rxjs';
import { HttpErrorResponse, HttpResponse } from '@angular/common/http';
import { AsyncPipe } from '@angular/common';
import { Router } from '@angular/router';
import { AddFriendResponse } from '../../models/add-friend-response';

@Component({
  selector: 'app-friends',
  imports: [AsyncPipe],
  templateUrl: './friends.html',
  styleUrl: './friends.css',
})
export class Friends {
  private friendsService = inject(FriendsService);
  private router = inject(Router)
  data = signal<AddFriendResponse | null>(null);
  error = signal<string | null>(null);

  friends$ = this.friendsService.getFriends().pipe(
    catchError((err: HttpErrorResponse) => {
      console.error('Failed to load friends', err.status, err.message);
      return of(null);
    }),
  )

  friendRequests$ = this.friendsService.getFriendRequests().pipe(
    catchError((err: HttpErrorResponse) => {
      console.error("Failed to load pending friend requests", err.status, err.message);
      return of(null);
    })
  )

  acceptFriendRequest(id: number): void {
    this.friendsService.acceptFriendRequest(id).subscribe({
      next: ((response) => {
        this.data.set(response);
      }),
      error: (err: HttpErrorResponse) => {
        this.error.set("Could not accept friend request. Try again.");
      }
      
    })
  }

  removeFriendRequest(id: number): void {
    this.friendsService.removeFriendRequest(id).subscribe({
      next: (() => {

      }),
      error: (err: HttpErrorResponse) => {
        this.error.set("Coudl not remove friend request. Try again.");
      } 
    })
  }

  protected readonly enviroment = "https://track-your-time.ddev.site/uploads/profile/";
}
