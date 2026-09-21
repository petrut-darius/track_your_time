import { TestBed } from '@angular/core/testing';

import { Friends } from '../services/friends';

describe('Friends', () => {
  let service: Friends;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Friends);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
