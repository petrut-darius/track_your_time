import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CircuitsIndex } from './circuits-index';

describe('CircuitsIndex', () => {
  let component: CircuitsIndex;
  let fixture: ComponentFixture<CircuitsIndex>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CircuitsIndex],
    }).compileComponents();

    fixture = TestBed.createComponent(CircuitsIndex);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
