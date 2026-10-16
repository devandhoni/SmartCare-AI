import type { UserRole } from "../types/auth";

export type MobileModule =
  | "Today"
  | "Residents"
  | "Medication"
  | "FamilyMessages"
  | "FamilyContacts"
  | "NurseTasks"
  | "CareRecords"
  | "CarePlans"
  | "Admissions"
  | "Discharges"
  | "Inventory"
  | "Billing"
  | "MonthlyGlucose"
  | "WeeklyVitals"
  | "Visitors"
  | "HomeLeave"
  | "Parcels"
  | "AIIntelligence"
  | "StaffAdministration"
  | "Reports"
  | "ManagementDashboard";

export interface MobileMenuItem {
  name: string;
  route: MobileModule;
  roles: UserRole[];
}

export interface MobileMenuSection {
  title: string | null;
  items: MobileMenuItem[];
}

const clinicalRoles: UserRole[] = [
  "Administrator",
  "Nurse",
];

const administratorRoles: UserRole[] = [
  "Administrator",
];

export const primaryModules: MobileMenuItem[] = [
  {
    name: "Today",
    route: "Today",
    roles: clinicalRoles,
  },
  {
    name: "Residents",
    route: "Residents",
    roles: clinicalRoles,
  },
  {
    name: "Medication",
    route: "Medication",
    roles: clinicalRoles,
  },
  {
    name: "Nurse Tasks",
    route: "NurseTasks",
    roles: clinicalRoles,
  },
];

export const moreMenuSections: MobileMenuSection[] = [
  {
    title: null,
    items: [
      {
        name: "Family Contacts",
        route: "FamilyContacts",
        roles: clinicalRoles,
      },
      {
        name: "Family Messages",
        route: "FamilyMessages",
        roles: clinicalRoles,
      },
      {
        name: "Care Records",
        route: "CareRecords",
        roles: clinicalRoles,
      },
      {
        name: "Care Plans",
        route: "CarePlans",
        roles: clinicalRoles,
      },
      {
        name: "Admissions",
        route: "Admissions",
        roles: clinicalRoles,
      },
      {
        name: "Discharges",
        route: "Discharges",
        roles: clinicalRoles,
      },
      {
        name: "Inventory",
        route: "Inventory",
        roles: clinicalRoles,
      },
      {
        name: "Billing",
        route: "Billing",
        roles: clinicalRoles,
      },
      {
        name: "Monthly Glucose",
        route: "MonthlyGlucose",
        roles: clinicalRoles,
      },
      {
        name: "Weekly Vitals",
        route: "WeeklyVitals",
        roles: clinicalRoles,
      },
      {
        name: "Visitors",
        route: "Visitors",
        roles: clinicalRoles,
      },
      {
        name: "Home Leave",
        route: "HomeLeave",
        roles: clinicalRoles,
      },
      {
        name: "Parcels",
        route: "Parcels",
        roles: clinicalRoles,
      },
    ],
  },
  {
    title: "Intelligence",
    items: [
      {
        name: "AI Intelligence",
        route: "AIIntelligence",
        roles: clinicalRoles,
      },
    ],
  },
  {
    title: "Management",
    items: [
      {
        name: "Staff Administration",
        route: "StaffAdministration",
        roles: administratorRoles,
      },
      {
        name: "Reports",
        route: "Reports",
        roles: administratorRoles,
      },
      {
        name: "Management Dashboard",
        route: "ManagementDashboard",
        roles: administratorRoles,
      },
    ],
  },
];

export function getVisibleMoreMenu(
  role: UserRole
): MobileMenuSection[] {
  return moreMenuSections
    .map((section) => ({
      ...section,
      items: section.items.filter((item) =>
        item.roles.includes(role)
      ),
    }))
    .filter((section) => section.items.length > 0);
}