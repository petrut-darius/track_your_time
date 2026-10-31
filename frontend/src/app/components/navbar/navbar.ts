import { Component, ElementRef, HostListener, inject, signal } from '@angular/core';
import { Auth } from '../../services/auth';
import { AsyncPipe, CommonModule } from '@angular/common';
import { Router, RouterLink, RouterLinkActive } from '@angular/router';
import { NotificationService } from '../../services/notification';
import { AppNotification } from '../../models/notification';

@Component({
  selector: 'app-navbar',
  imports: [RouterLink, RouterLinkActive, AsyncPipe, CommonModule],
  templateUrl: './navbar.html',
  styleUrl: './navbar.css',
})
export class Navbar {
  protected authService = inject(Auth);
  protected notificationService = inject(NotificationService);
  private router = inject(Router);
  private elementRef = inject(ElementRef);

  protected isNotificationsOpen = signal<boolean>(false);

  protected toggleNotifications(): void {
    this.isNotificationsOpen.update((open) => !open);
  }

  protected closeNotifications(): void {
    this.isNotificationsOpen.set(false);
  }

  protected onNotificationClick(notification: AppNotification): void {
    if (!notification.readAt) {
      this.notificationService.markAsRead(notification.id);
    }
    this.closeNotifications();

    if (notification.type === 'profile-edit' && notification.targetId) {
      this.router.navigate(['/profile', notification.targetId]);
    }
  }

  protected onMarkAllAsRead(event: Event): void {
    event.stopPropagation();
    this.notificationService.markAllAsRead();
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    if (!this.elementRef.nativeElement.contains(event.target)) {
      this.closeNotifications();
    }
  }

  protected onLogout(): void {
    this.closeNotifications();
    this.authService.logout().subscribe();
  }
}
