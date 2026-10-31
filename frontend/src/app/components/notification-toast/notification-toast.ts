import { Component, inject, OnInit, OnDestroy, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { NotificationService } from '../../services/notification';
import { AppNotification } from '../../models/notification';

interface ToastItem {
  notification: AppNotification;
  timerId: any;
}

@Component({
  selector: 'app-notification-toast',
  imports: [CommonModule],
  templateUrl: './notification-toast.html',
  styleUrl: './notification-toast.css',
})
export class NotificationToast implements OnInit, OnDestroy {
  private notificationService = inject(NotificationService);
  private router = inject(Router);

  public toasts = signal<ToastItem[]>([]);
  private subscription: Subscription | null = null;

  ngOnInit(): void {
    this.subscription = this.notificationService.incomingToast$.subscribe((notif) => {
      this.addToast(notif);
    });
  }

  addToast(notification: AppNotification): void {
    const timerId = setTimeout(() => {
      this.removeToast(notification.id);
    }, 6000);

    this.toasts.update((current) => [...current, { notification, timerId }]);
  }

  removeToast(id: number): void {
    this.toasts.update((current) => {
      const item = current.find((t) => t.notification.id === id);
      if (item) {
        clearTimeout(item.timerId);
      }
      return current.filter((t) => t.notification.id !== id);
    });
  }

  handleClick(notification: AppNotification): void {
    this.notificationService.markAsRead(notification.id);
    this.removeToast(notification.id);

    if (notification.type === 'profile-edit' && notification.targetId) {
      this.router.navigate(['/profile', notification.targetId]);
    }
  }

  ngOnDestroy(): void {
    if (this.subscription) {
      this.subscription.unsubscribe();
    }
    for (const toast of this.toasts()) {
      clearTimeout(toast.timerId);
    }
  }
}
