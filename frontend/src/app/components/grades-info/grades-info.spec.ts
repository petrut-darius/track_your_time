import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GradesInfo } from './grades-info';

describe('GradesInfo', () => {
  let component: GradesInfo;
  let fixture: ComponentFixture<GradesInfo>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [GradesInfo],
    }).compileComponents();

    fixture = TestBed.createComponent(GradesInfo);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
