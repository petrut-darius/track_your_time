import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CarIndex } from './car-index';

describe('CarIndex', () => {
  let component: CarIndex;
  let fixture: ComponentFixture<CarIndex>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CarIndex],
    }).compileComponents();

    fixture = TestBed.createComponent(CarIndex);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
