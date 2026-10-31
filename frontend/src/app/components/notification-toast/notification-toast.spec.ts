import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';
import { NotificationToast } from './notification-toast';
import { NotificationService } from '../../services/notification';

describe('NotificationToast', () => {
  let component: NotificationToast;
  let fixture: ComponentFixture<NotificationToast>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [NotificationToast],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
        NotificationService,
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(NotificationToast);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should add and remove toasts', () => {
    const notif = {
      id: 1,
      type: 'profile-edit',
      content: 'User updated profile',
      targetId: 2,
      readAt: null,
    };

    component.addToast(notif);
    expect(component.toasts().length).toBe(1);
    expect(component.toasts()[0].notification.id).toBe(1);

    component.removeToast(1);
    expect(component.toasts().length).toBe(0);
  });
});
