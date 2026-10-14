import React from "react";
import {
  SafeAreaView,
  StyleSheet,
  Text,
  View,
} from "react-native";

import { useAuth } from "../../context/AuthContext";

export default function AdminDashboardScreen() {
  const { user, logout } = useAuth();

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>
          SmartCare AI
        </Text>

        <Text style={styles.heading}>
          Administrator Dashboard
        </Text>

        <Text style={styles.text}>
          Signed in as {user?.full_name || user?.name || user?.email}
        </Text>

        <Text
          style={styles.logout}
          onPress={logout}
        >
          Logout
        </Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f1f5f9",
  },

  content: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    padding: 24,
  },

  title: {
    fontSize: 30,
    fontWeight: "700",
    color: "#0f172a",
    marginBottom: 12,
  },

  heading: {
    fontSize: 20,
    fontWeight: "600",
    color: "#334155",
    marginBottom: 12,
  },

  text: {
    fontSize: 15,
    color: "#64748b",
    textAlign: "center",
  },

  logout: {
    marginTop: 30,
    fontSize: 16,
    fontWeight: "700",
    color: "#2563eb",
  },
});