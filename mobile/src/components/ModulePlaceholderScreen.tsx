import React from "react";
import {
  StyleSheet,
  Text,
  View,
} from "react-native";
import {
  SafeAreaView,
} from "react-native-safe-area-context";

interface ModulePlaceholderScreenProps {
  title: string;
}

export default function ModulePlaceholderScreen({
  title,
}: ModulePlaceholderScreenProps) {
  return (
    <SafeAreaView
      style={styles.safeArea}
      edges={["bottom"]}
    >
      <View style={styles.container}>
        <Text style={styles.title}>{title}</Text>

        <Text style={styles.message}>
          This mobile module will be connected to the
          existing SmartCare-AI workflow during its
          implementation stage.
        </Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: "#f1f5f9",
  },

  container: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 24,
  },

  title: {
    fontSize: 24,
    fontWeight: "700",
    color: "#0f172a",
    textAlign: "center",
  },

  message: {
    maxWidth: 360,
    marginTop: 12,
    fontSize: 15,
    lineHeight: 22,
    color: "#64748b",
    textAlign: "center",
  },
});