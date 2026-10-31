import { TestBed } from '@angular/core/testing';

import { CircuitTime } from './circuit-time';

describe('CircuitTime', () => {
  let service: CircuitTime;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(CircuitTime);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
