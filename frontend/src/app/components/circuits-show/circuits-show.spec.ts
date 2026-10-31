import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CircuitsShow } from './circuits-show';

describe('CircuitsShow', () => {
  let component: CircuitsShow;
  let fixture: ComponentFixture<CircuitsShow>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CircuitsShow],
    }).compileComponents();

    fixture = TestBed.createComponent(CircuitsShow);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
