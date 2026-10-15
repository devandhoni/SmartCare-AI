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

export type AppStackParamList = {
  MainTabs: undefined;
  Admissions: undefined;
  Discharges: undefined;
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
  if (module === "Admissions") {
    navigation.navigate("Admissions");
    return;
  }
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
              ☀
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
              👥
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
              💊
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
              ✓
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
              ⋯
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

      <Stack.Screen name="Discharges" component={DischargesScreen} options={{ title: "Discharges", headerBackTitle: "Back" }} />
      <Stack.Screen name="DischargeWizard" component={DischargeWizardScreen} options={{ title: "Discharge Wizard", headerBackTitle: "Discharges" }} />
      <Stack.Screen name="Admissions" component={AdmissionsScreen} options={{ title: "Admissions", headerBackTitle: "Back" }} />
      <Stack.Screen name="AdmissionWizard" component={AdmissionWizardScreen} options={{ title: "Admission Wizard", headerBackTitle: "Admissions" }} />
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