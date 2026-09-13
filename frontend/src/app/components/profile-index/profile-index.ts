import { Component, inject } from '@angular/core';
import { AsyncPipe, CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Profile } from '../../services/profile';
import { catchError, combineLatest, map, of, switchMap } from 'rxjs';
import { Auth } from '../../services/auth';
import { HttpErrorResponse } from '@angular/common/http';

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

  protected readonly enviroment = "https://track-your-time.ddev.site/uploads/profile/";


  user$ = this.route.paramMap.pipe(
    map(params => params.get("id")),
    switchMap(id => this.profileService.getProfileById(id!).pipe(
      catchError((err: HttpErrorResponse) => {
        console.error('Failed to load profile', err.status, err.message);
        return of(null);
      }),
    )),
  )

  isOwnProfile$ = combineLatest([this.user$, this.authService.user$]).pipe(
    map(([viewedUser, currentUser]) => 
      viewedUser !== null && currentUser !== null && viewedUser.id === currentUser.id
    ),
  )
}
