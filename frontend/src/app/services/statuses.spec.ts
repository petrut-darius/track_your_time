import { TestBed } from '@angular/core/testing';

import { Statuses } from './statuses';

describe('Statuses', () => {
  let service: Statuses;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Statuses);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
