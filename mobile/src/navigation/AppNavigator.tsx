import React from "react";
import { Text } from "react-native";
import {
  createBottomTabNavigator,
} from "@react-navigation/bottom-tabs";
import {
  createNativeStackNavigator,
  type NativeStackScreenProps,
} from "@react-navigation/native-stack";

import TodayScreen from "../screens/dashboard/TodayScreen";
import MoreScreen from "../screens/dashboard/MoreScreen";
import ModulePlaceholderScreen from "../components/ModulePlaceholderScreen";
import ResidentsScreen from "../screens/residents/ResidentsScreen";
import ResidentProfileScreen from "../screens/residents/ResidentProfileScreen";
import ResidentVitalsScreen from "../screens/vitals/ResidentVitalsScreen";
import RecordVitalsScreen from "../screens/vitals/RecordVitalsScreen";
import MedicationScreen from "../screens/medication/MedicationScreen";
import NurseTasksScreen from "../screens/care/NurseTasksScreen";
import CareRecordsScreen from "../screens/care/CareRecordsScreen";
import CarePlansScreen from "../screens/care/CarePlansScreen";
import type { MobileModule } from "./menuConfig";
import AdmissionsScreen from "../screens/admissions/AdmissionsScreen";
import AdmissionWizardScreen from "../screens/admissions/AdmissionWizardScreen";
import DischargesScreen from "../screens/discharges/DischargesScreen";
import DischargeWizardScreen from "../screens/discharges/DischargeWizardScreen";
import MonthlyGlucoseScreen from "../screens/monitoring/MonthlyGlucoseScreen";
import WeeklyVitalsScreen from "../screens/monitoring/WeeklyVitalsScreen";
import InventoryScreen from "../screens/inventory/InventoryScreen";
import FamilyContactsScreen from "../screens/family/FamilyContactsScreen";
import FamilyMessagesScreen from "../screens/family/FamilyMessagesScreen";
import AIIntelligenceScreen from "../screens/ai/AIIntelligenceScreen";
import ResidentTimelineScreen from "../screens/residents/ResidentTimelineScreen";
import ResidentDocumentsScreen from "../screens/residents/ResidentDocumentsScreen";

import VisitorsScreen from "../screens/operations/VisitorsScreen";
import HomeLeaveScreen from "../screens/operations/HomeLeaveScreen";
import ParcelsScreen from "../screens/operations/ParcelsScreen";
import BillingScreen from "../screens/billing/BillingScreen";
import ReportsScreen from "../screens/reports/ReportsScreen";
import StaffAdministrationScreen from "../screens/staff/StaffAdministrationScreen";
import AuditHistoryScreen from "../screens/audit/AuditHistoryScreen";
export type AppStackParamList = {
  MainTabs: undefined;
  FamilyContacts: undefined;
  FamilyMessages: undefined;
  AIIntelligence: undefined;
  Parcels: undefined;
  HomeLeave: undefined;
  Visitors: undefined;
  Admissions: undefined;
  Discharges: undefined;
  MonthlyGlucose: undefined;
  WeeklyVitals: undefined;
  Inventory: undefined;
  Billing: undefined;
  Reports: undefined;
  StaffAdministration: undefined;
  AuditHistory: undefined;
  DischargeWizard: { dischargeId?: number } | undefined;
  AdmissionWizard: { draftId?: number } | undefined;
  CarePlans: undefined;
  CareRecords: undefined;
  Module: {
    module: MobileModule;
    title: string;
  };
  ResidentProfile: {
    residentId: number;
  };
  ResidentTimeline: { residentId: number };
  ResidentDocuments: { residentId: number };
  ResidentVitals: {
    residentId: number;
  };
  RecordVitals: {
    residentId: number;
  };
};

type MainTabParamList = {
  Today: undefined;
  Residents: undefined;
  Medication: undefined;
  NurseTasks: undefined;
  More: undefined;
};

const Stack =
  createNativeStackNavigator<AppStackParamList>();

const Tab =
  createBottomTabNavigator<MainTabParamList>();

const moduleTitles: Record<MobileModule, string> = {
  Today: "Today",
  Residents: "Residents",
  Medication: "Medication",
  FamilyMessages: "Family Messages",
  FamilyContacts: "Family Contacts",
  NurseTasks: "Nurse Tasks",
  CareRecords: "Care Records",
  CarePlans: "Care Plans",
  Admissions: "Admissions",
  Discharges: "Discharges",
  Inventory: "Inventory",
  Billing: "Billing",
  MonthlyGlucose: "Monthly Glucose",
  WeeklyVitals: "Weekly Vitals",
  Visitors: "Visitors",
  HomeLeave: "Home Leave",
  Parcels: "Parcels",
  AIIntelligence: "AI Intelligence",
  StaffAdministration: "Staff Administration",
  AuditHistory: "Audit History",
  Reports: "Reports",
  ManagementDashboard: "Management Dashboard",
};

function ResidentsPlaceholder() {
  return (
    <ModulePlaceholderScreen title="Residents" />
  );
}

function MedicationPlaceholder() {
  return (
    <ModulePlaceholderScreen title="Medication" />
  );
}

function NurseTasksPlaceholder() {
  return (
    <ModulePlaceholderScreen title="Nurse Tasks" />
  );
}

function MainTabs({
  navigation,
}: NativeStackScreenProps<
  AppStackParamList,
  "MainTabs"
>) {
  const openModule = (module: MobileModule) => {
  if (module === "AIIntelligence") { navigation.navigate("AIIntelligence"); return; }
  if (module === "FamilyContacts") { navigation.navigate("FamilyContacts"); return; }
  if (module === "FamilyMessages") { navigation.navigate("FamilyMessages"); return; }
  if (module === "Visitors") { navigation.navigate("Visitors"); return; }
  if (module === "HomeLeave") { navigation.navigate("HomeLeave"); return; }
  if (module === "Parcels") { navigation.navigate("Parcels"); return; }
  if (module === "Billing") { navigation.navigate("Billing"); return; }
  if (module === "Reports") { navigation.navigate("Reports"); return; }
  if (module === "StaffAdministration") { navigation.navigate("StaffAdministration"); return; }
  if (module === "AuditHistory") { navigation.navigate("AuditHistory"); return; }
  if (module === "Admissions") {
    navigation.navigate("Admissions");
    return;
  }
  if (module === "MonthlyGlucose") { navigation.navigate("MonthlyGlucose"); return; }
  if (module === "WeeklyVitals") { navigation.navigate("WeeklyVitals"); return; }
  if (module === "Discharges") {
    navigation.navigate("Discharges");
    return;
  }
  if (module === "CareRecords") {
    navigation.navigate("CareRecords");
    return;
  }

  if (module === "CarePlans") {
  navigation.navigate("CarePlans");
  return;
}

if (module === "Inventory") {
  navigation.navigate("Inventory");
  return;
}

  navigation.navigate("Module", {
    module,
    title: moduleTitles[module],
  });
};

  return (
    <Tab.Navigator
      screenOptions={{
        headerStyle: {
          backgroundColor: "#ffffff",
        },
        headerTitleStyle: {
          fontWeight: "700",
        },
        headerTintColor: "#0f172a",
        tabBarActiveTintColor: "#2563eb",
        tabBarInactiveTintColor: "#64748b",
        tabBarStyle: {
          height: 64,
          paddingTop: 6,
          paddingBottom: 8,
        },
      }}
    >
      <Tab.Screen
        name="Today"
        component={TodayScreen}
        options={{
          title: "Today",
          tabBarIcon: ({ color }) => (
            <Text style={{ color, fontSize: 20 }}>
              {"\u2600"}
            </Text>
          ),
        }}
      />

      <Tab.Screen
        name="Residents"
        component={ResidentsScreen}
        options={{
          title: "Residents",
          tabBarIcon: ({ color }) => (
            <Text style={{ color, fontSize: 20 }}>
              {"\uD83D\uDC65"}
            </Text>
          ),
        }}
      />

      <Tab.Screen
        name="Medication"
        component={MedicationScreen}
        options={{
          title: "Medication",
          tabBarIcon: ({ color }) => (
            <Text style={{ color, fontSize: 20 }}>
              {"\uD83D\uDC8A"}
            </Text>
          ),
        }}
      />

      <Tab.Screen
        name="NurseTasks"
        component={NurseTasksScreen}
        options={{
          title: "Tasks",
          tabBarIcon: ({ color }) => (
            <Text style={{ color, fontSize: 20 }}>
              {"\u2713"}
            </Text>
          ),
        }}
      />

      <Tab.Screen
        name="More"
        options={{
          title: "More",
          tabBarIcon: ({ color }) => (
            <Text style={{ color, fontSize: 22 }}>
              {"\u22EF"}
            </Text>
          ),
        }}
      >
        {() => (
          <MoreScreen
            onOpenModule={openModule}
          />
        )}
      </Tab.Screen>
    </Tab.Navigator>
  );
}

function ModuleScreen({
  route,
}: NativeStackScreenProps<
  AppStackParamList,
  "Module"
>) {
  return (
    <ModulePlaceholderScreen
      title={route.params.title}
    />
  );
}

export default function AppNavigator() {
  return (
    <Stack.Navigator>
      <Stack.Screen
        name="MainTabs"
        component={MainTabs}
        options={{
          headerShown: false,
        }}
      />

      <Stack.Screen
        name="ResidentProfile"
        component={ResidentProfileScreen}
        options={{
          title: "Resident Profile",
        }}
      />

      <Stack.Screen name="ResidentTimeline" component={ResidentTimelineScreen} options={{ title: "Clinical Timeline" }} />
      <Stack.Screen name="ResidentDocuments" component={ResidentDocumentsScreen} options={{ title: "Resident Documents" }} />
      <Stack.Screen
        name="ResidentVitals"
        component={ResidentVitalsScreen}
        options={{
          title: "Vital Signs",
        }}
      />

      <Stack.Screen
        name="RecordVitals"
        component={RecordVitalsScreen}
        options={{
          title: "Record Vital Signs",
        }}
      />

      <Stack.Screen
        name="CarePlans"
        component={CarePlansScreen}
        options={{
          title: "Care Plans",
          headerBackTitle: "Back",
        }}
      />

      <Stack.Screen
        name="CareRecords"
        component={CareRecordsScreen}
        options={{
          title: "Care Records",
          headerBackTitle: "Back",
        }}
      />


      <Stack.Screen name="MonthlyGlucose" component={MonthlyGlucoseScreen} options={{ title: "Monthly Glucose", headerBackTitle: "Back" }} />
      <Stack.Screen name="WeeklyVitals" component={WeeklyVitalsScreen} options={{ title: "Weekly Vitals", headerBackTitle: "Back" }} />
      <Stack.Screen name="Discharges" component={DischargesScreen} options={{ title: "Discharges", headerBackTitle: "Back" }} />
      <Stack.Screen name="DischargeWizard" component={DischargeWizardScreen} options={{ title: "Discharge Wizard", headerBackTitle: "Discharges" }} />
      <Stack.Screen name="Admissions" component={AdmissionsScreen} options={{ title: "Admissions", headerBackTitle: "Back" }} />
      <Stack.Screen name="AdmissionWizard" component={AdmissionWizardScreen} options={{ title: "Admission Wizard", headerBackTitle: "Admissions" }} />

      <Stack.Screen name="Billing" component={BillingScreen} options={{ title: "Billing & Payments", headerBackTitle: "Back" }} />
      <Stack.Screen name="Reports" component={ReportsScreen} options={{ title: "Reports", headerBackTitle: "Back" }} />
      <Stack.Screen name="StaffAdministration" component={StaffAdministrationScreen} options={{ title: "Staff Administration", headerBackTitle: "Back" }} />
      <Stack.Screen name="AuditHistory" component={AuditHistoryScreen} options={{ title: "Audit History", headerBackTitle: "Back" }} />
      <Stack.Screen
        name="Inventory"
        component={InventoryScreen}
        options={{
          title: "Medicine Inventory",
          headerBackTitle: "Back",
        }}
      />

      
            <Stack.Screen name="FamilyContacts" component={FamilyContactsScreen} options={{ title: "Family Contacts" }} />
      <Stack.Screen name="FamilyMessages" component={FamilyMessagesScreen} options={{ title: "Family Messages" }} />
      <Stack.Screen name="AIIntelligence" component={AIIntelligenceScreen} options={{ title: "AI Intelligence" }} />
      <Stack.Screen name="Visitors" component={VisitorsScreen} options={{ title: "Visitors" }} />
      <Stack.Screen name="HomeLeave" component={HomeLeaveScreen} options={{ title: "Home Leave" }} />
      <Stack.Screen name="Parcels" component={ParcelsScreen} options={{ title: "Parcels" }} />
      <Stack.Screen
        name="Module"
        component={ModuleScreen}
        options={({ route }) => ({
          title: route.params.title,
          headerBackTitle: "Back",
        })}
      />
    </Stack.Navigator>
  );
}
