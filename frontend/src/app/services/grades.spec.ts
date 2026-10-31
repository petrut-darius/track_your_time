import { TestBed } from '@angular/core/testing';

import { Grades } from './grades';

describe('Grades', () => {
  let service: Grades;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Grades);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
