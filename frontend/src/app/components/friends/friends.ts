import { Component, inject, OnInit, signal } from '@angular/core';
import { Friends as FriendsService } from '../../services/friends';
import { BehaviorSubject, catchError, of, tap } from 'rxjs';
import { HttpErrorResponse, HttpResponse } from '@angular/common/http';
import { AsyncPipe } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { AddFriendResponse } from '../../models/add-friend-response';
import { FriendData } from '../../models/friend-data';

@Component({
  selector: 'app-friends',
  imports: [AsyncPipe, RouterLink],
  templateUrl: './friends.html',
  styleUrl: './friends.css',
})
export class Friends implements OnInit{
  private friendsService = inject(FriendsService);
  private router = inject(Router)
  data = signal<AddFriendResponse | null>(null);
  error = signal<string | null>(null);

  private friendsData$ = new BehaviorSubject<FriendData[] | null>(null);
  readonly friends$ = this.friendsData$.asObservable();

  ngOnInit(): void {
    this.friendsService.getFriends().pipe(
      tap((response) => {
        this.friendsData$.next(response);
      }),
      catchError((err: HttpErrorResponse) => {
        console.error('Failed to load friends', err.status, err.message);
        return of(null);
      }),
    ).subscribe();
  }

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
    if(confirm("Are you sure you want to delete this relationship?")) {
      this.friendsService.removeFriendRequest(id).subscribe({
        next: ((response) => {
          this.friendsData$.next(response);
        }),
        error: (err: HttpErrorResponse) => {
          this.error.set("Could not remove friend request. Try again.");
        } 
      })
    }
  }
  protected readonly enviroment = "/uploads/profile/";
}
