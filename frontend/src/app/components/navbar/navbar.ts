import { Component, inject } from '@angular/core';
import { Auth } from '../../services/auth';
import { AsyncPipe, CommonModule } from '@angular/common';
import { RouterLink, RouterLinkActive } from '@angular/router';

@Component({
  selector: 'app-navbar',
  imports: [RouterLink, RouterLinkActive, AsyncPipe],
  templateUrl: './navbar.html',
  styleUrl: './navbar.css',
})
export class Navbar {
  protected authService = inject(Auth);

  protected onLogout(): void {
    this.authService.logout().subscribe();
  }
}
