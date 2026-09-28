import { TestBed } from '@angular/core/testing';

import { Circuit } from './circuit';

describe('Circuit', () => {
  let service: Circuit;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Circuit);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
