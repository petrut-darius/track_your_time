import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FriendsLeaderboard } from './friends-leaderboard';

describe('FriendsLeaderboard', () => {
  let component: FriendsLeaderboard;
  let fixture: ComponentFixture<FriendsLeaderboard>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FriendsLeaderboard],
    }).compileComponents();

    fixture = TestBed.createComponent(FriendsLeaderboard);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
