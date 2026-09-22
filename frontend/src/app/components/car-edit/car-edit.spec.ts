import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CarEdit } from './car-edit';

describe('CarEdit', () => {
  let component: CarEdit;
  let fixture: ComponentFixture<CarEdit>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CarEdit],
    }).compileComponents();

    fixture = TestBed.createComponent(CarEdit);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
