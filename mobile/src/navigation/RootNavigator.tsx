import React from "react";
import {
  ActivityIndicator,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { NavigationContainer } from "@react-navigation/native";
import { createNativeStackNavigator } from "@react-navigation/native-stack";

import { useAuth } from "../context/AuthContext";
import LoginScreen from "../screens/auth/LoginScreen";
import AppNavigator from "./AppNavigator";

export type RootStackParamList = {
  Login: undefined;
  App: undefined;
};

const Stack =
  createNativeStackNavigator<RootStackParamList>();

export default function RootNavigator() {
  const {
    isLoading,
    isAuthenticated,
  } = useAuth();

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" />

        <Text style={styles.loadingText}>
          Loading SmartCare AI...
        </Text>
      </View>
    );
  }

  return (
    <NavigationContainer>
      <Stack.Navigator
        screenOptions={{
          headerShown: false,
        }}
      >
        {!isAuthenticated ? (
          <Stack.Screen
            name="Login"
            component={LoginScreen}
          />
        ) : (
          <Stack.Screen
            name="App"
            component={AppNavigator}
          />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#f1f5f9",
  },

  loadingText: {
    marginTop: 12,
    fontSize: 15,
    color: "#64748b",
  },
});