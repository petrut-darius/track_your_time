import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CountriesIndex } from './countries-index';

describe('CountriesIndex', () => {
  let component: CountriesIndex;
  let fixture: ComponentFixture<CountriesIndex>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CountriesIndex],
    }).compileComponents();

    fixture = TestBed.createComponent(CountriesIndex);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
