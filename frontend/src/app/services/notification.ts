import { HttpClient } from '@angular/common/http';
import { inject, Injectable, OnDestroy, signal } from '@angular/core';
import { Subject, Subscription } from 'rxjs';
import { AppNotification, NotificationListResponse, NotificationSubscriptionResponse } from '../models/notification';
import { Auth } from './auth';

@Injectable({
  providedIn: 'root',
})
export class NotificationService implements OnDestroy {
  private http = inject(HttpClient);
  private authService = inject(Auth);

  public readonly notifications = signal<AppNotification[]>([]);
  public readonly unreadCount = signal<number>(0);
  public readonly isConnected = signal<boolean>(false);

  private incomingToastSubject = new Subject<AppNotification>();
  public readonly incomingToast$ = this.incomingToastSubject.asObservable();

  private authSubscription: Subscription | null = null;
  private abortController: AbortController | null = null;
  private reconnectTimeoutId: any = null;
  private currentUserId: number | null = null;
  private isDestroyed = false;

  constructor() {
    this.authSubscription = this.authService.user$.subscribe((user) => {
      if (user && user.id) {
        if (this.currentUserId !== user.id) {
          this.currentUserId = user.id;
          this.initNotifications(user.id);
        }
      } else {
        this.currentUserId = null;
        this.disconnect();
        this.notifications.set([]);
        this.unreadCount.set(0);
      }
    });
  }

  private initNotifications(userId: number): void {
    this.fetchNotifications();
    this.connect(userId);
  }

  public fetchNotifications(): void {
    this.http.get<NotificationListResponse>('/api/notifications', { withCredentials: true }).subscribe({
      next: (res) => {
        this.notifications.set(res.data);
        this.unreadCount.set(res.unreadCount);
      },
      error: (err) => {
        console.error('Failed to fetch notifications:', err);
      },
    });
  }

  private async connect(userId: number): Promise<void> {
    this.disconnect();

    try {
      const subscription = await this.http
        .get<NotificationSubscriptionResponse>('/api/notifications/subscribe', { withCredentials: true })
        .toPromise();

      if (!subscription || !subscription.token) {
        return;
      }

      this.abortController = new AbortController();
      const signal = this.abortController.signal;

      // Construct SSE URL with match parameter
      const streamUrl = new URL(subscription.hubUrl, window.location.origin);
      streamUrl.searchParams.set('match', subscription.topic);
      streamUrl.searchParams.set('topic', subscription.topic);

      const response = await fetch(streamUrl.toString(), {
        signal,
        headers: {
          Accept: 'text/event-stream',
          Authorization: `Bearer ${subscription.token}`,
        },
      });

      if (!response.ok || !response.body) {
        throw new Error(`SSE stream failed with status ${response.status}`);
      }

      this.isConnected.set(true);

      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';

      while (!signal.aborted) {
        const { value, done } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        buffer = lines.pop() || '';

        for (const line of lines) {
          const trimmed = line.trim();
          if (trimmed.startsWith('data:')) {
            const jsonStr = trimmed.slice(5).trim();
            if (jsonStr) {
              try {
                const notif = JSON.parse(jsonStr) as AppNotification;
                this.handleIncomingNotification(notif);
              } catch (parseErr) {
                console.error('Error parsing live notification:', parseErr);
              }
            }
          }
        }
      }
    } catch (err: any) {
      if (this.isDestroyed || err.name === 'AbortError') {
        return;
      }
      this.isConnected.set(false);
      // Auto-reconnect with backoff
      this.reconnectTimeoutId = setTimeout(() => {
        if (!this.isDestroyed && this.currentUserId) {
          this.connect(this.currentUserId);
        }
      }, 5000);
    } finally {
      if (!this.isDestroyed) {
        this.isConnected.set(false);
      }
    }
  }

  private handleIncomingNotification(notif: AppNotification): void {
    // Prepend to notifications list avoiding duplicates
    this.notifications.update((list) => [notif, ...list.filter((item) => item.id !== notif.id)]);
    this.unreadCount.update((count) => count + 1);

    // Notify toast alert
    this.incomingToastSubject.next(notif);
  }

  public markAsRead(id: number): void {
    this.http.patch<{ data: AppNotification }>(`/api/notifications/${id}/read`, {}, { withCredentials: true }).subscribe({
      next: (res) => {
        this.notifications.update((list) =>
          list.map((item) => (item.id === id ? { ...item, readAt: res.data.readAt || new Date().toISOString() } : item))
        );
        this.unreadCount.update((c) => Math.max(0, c - 1));
      },
      error: (err) => console.error('Failed to mark notification as read:', err),
    });
  }

  public markAllAsRead(): void {
    this.http.post<{ success: boolean; updatedCount: number }>('/api/notifications/read-all', {}, { withCredentials: true }).subscribe({
      next: () => {
        const now = new Date().toISOString();
        this.notifications.update((list) =>
          list.map((item) => ({ ...item, readAt: item.readAt || now }))
        );
        this.unreadCount.set(0);
      },
      error: (err) => console.error('Failed to mark all notifications as read:', err),
    });
  }

  public disconnect(): void {
    if (this.reconnectTimeoutId) {
      clearTimeout(this.reconnectTimeoutId);
      this.reconnectTimeoutId = null;
    }
    if (this.abortController) {
      this.abortController.abort();
      this.abortController = null;
    }
    this.isConnected.set(false);
  }

  ngOnDestroy(): void {
    this.isDestroyed = true;
    this.disconnect();
    if (this.authSubscription) {
      this.authSubscription.unsubscribe();
    }
  }
}
