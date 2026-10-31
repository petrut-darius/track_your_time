export interface AppNotification {
  id: number;
  type: string;
  content: string;
  targetId: number | null;
  readAt: string | null;
}

export interface NotificationListResponse {
  data: AppNotification[];
  unreadCount: number;
}

export interface NotificationSubscriptionResponse {
  hubUrl: string;
  topic: string;
  token: string;
}
