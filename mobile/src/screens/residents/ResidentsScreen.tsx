import React, {
  useCallback,
  useEffect,
  useMemo,
  useState,
} from "react";
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { useNavigation } from "@react-navigation/native";

import { getResidents } from "../../api/residentApi";
import type { Resident } from "../../types/resident";

export default function ResidentsScreen() {
  const navigation = useNavigation<any>();

  const [residents, setResidents] = useState<Resident[]>([]);
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState("");

  const loadResidents = useCallback(
    async (isRefresh = false) => {
      try {
        if (isRefresh) {
          setRefreshing(true);
        } else {
          setLoading(true);
        }

        setError("");

        const rows = await getResidents();
        setResidents(rows);
      } catch (requestError: any) {
        console.error(
          "Failed to load residents:",
          requestError
        );

        setError(
          requestError?.response?.data?.message ||
            "Unable to load residents. Please try again."
        );
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    []
  );

  useEffect(() => {
    loadResidents();
  }, [loadResidents]);

  const visibleResidents = useMemo(() => {
    const query = search.trim().toLowerCase();

    if (!query) {
      return residents;
    }

    return residents.filter((resident) => {
      const name = String(
        resident.full_name ||
          resident.name ||
          ""
      ).toLowerCase();

      const room = String(
        resident.room_number ||
          resident.room ||
          ""
      ).toLowerCase();

      return (
        name.includes(query) ||
        room.includes(query)
      );
    });
  }, [residents, search]);

  const openResident = (resident: Resident) => {
    navigation
      .getParent()
      ?.navigate("ResidentProfile", {
        residentId: resident.id,
      });
  };

  return (
    <SafeAreaView
      style={styles.safeArea}
      edges={["top"]}
    >
      <FlatList
        data={visibleResidents}
        keyExtractor={(item) =>
          String(item.id)
        }
        renderItem={({ item }) => (
          <ResidentRow
            resident={item}
            onPress={() => openResident(item)}
          />
        )}
        keyboardShouldPersistTaps="handled"
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() =>
              loadResidents(true)
            }
          />
        }
        ListHeaderComponent={
          <>
            <View style={styles.header}>
              <Text style={styles.eyebrow}>
                Residents
              </Text>

              <Text style={styles.title}>
                Current Residents
              </Text>

              <Text style={styles.subtitle}>
                Select a resident to view their
                full care record.
              </Text>

              <View style={styles.controls}>
                <View style={styles.countCard}>
                  <Text style={styles.countLabel}>
                    CURRENT
                  </Text>

                  <Text style={styles.countValue}>
                    {residents.length}
                  </Text>
                </View>

                <View style={styles.searchArea}>
                  <Text style={styles.searchLabel}>
                    Find Resident
                  </Text>

                  <TextInput
                    value={search}
                    onChangeText={setSearch}
                    placeholder="Search by name or room..."
                    placeholderTextColor="#94a3b8"
                    autoCapitalize="none"
                    autoCorrect={false}
                    returnKeyType="search"
                    style={styles.searchInput}
                  />
                </View>
              </View>
            </View>

            {error ? (
              <View style={styles.errorCard}>
                <Text style={styles.errorText}>
                  {error}
                </Text>

                <Pressable
                  onPress={() =>
                    loadResidents()
                  }
                >
                  <Text style={styles.retryText}>
                    Try Again
                  </Text>
                </Pressable>
              </View>
            ) : null}

            {!loading && !error ? (
              <View style={styles.resultsHeader}>
                <Text style={styles.resultsText}>
                  {search.trim()
                    ? `${visibleResidents.length} result${
                        visibleResidents.length === 1
                          ? ""
                          : "s"
                      }`
                    : `${residents.length} resident${
                        residents.length === 1
                          ? ""
                          : "s"
                      }`}
                </Text>
              </View>
            ) : null}
          </>
        }
        ListEmptyComponent={
          loading ? (
            <View style={styles.stateContainer}>
              <ActivityIndicator size="large" />

              <Text style={styles.stateText}>
                Loading residents...
              </Text>
            </View>
          ) : error ? null : (
            <View style={styles.stateContainer}>
              <Text style={styles.emptyIcon}>
                👥
              </Text>

              <Text style={styles.emptyTitle}>
                No residents found
              </Text>

              <Text style={styles.stateText}>
                Try another name or room number.
              </Text>
            </View>
          )
        }
        contentContainerStyle={
          visibleResidents.length === 0
            ? styles.emptyContent
            : styles.content
        }
      />
    </SafeAreaView>
  );
}

function ResidentRow({
  resident,
  onPress,
}: {
  resident: Resident;
  onPress: () => void;
}) {
  const name =
    resident.full_name ||
    resident.name ||
    "Unnamed Resident";

  const room =
    resident.room_number ||
    resident.room ||
    "Room not assigned";

  const initials = name
    .split(" ")
    .filter(Boolean)
    .map((part) => part[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();

  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.residentCard,
        pressed && styles.pressed,
      ]}
    >
      <View style={styles.avatar}>
        <Text style={styles.avatarText}>
          {initials || "R"}
        </Text>
      </View>

      <View style={styles.residentInfo}>
        <Text
          style={styles.residentName}
          numberOfLines={2}
        >
          {name}
        </Text>

        <Text style={styles.residentMeta}>
          {room}
          {resident.gender
            ? ` • ${resident.gender}`
            : ""}
        </Text>

        {resident.status ? (
          <View style={styles.statusBadge}>
            <Text style={styles.statusText}>
              {resident.status}
            </Text>
          </View>
        ) : null}
      </View>

      <Text style={styles.arrow}>
        ›
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: "#f8fafc",
  },

  content: {
    padding: 16,
    paddingBottom: 32,
  },

  emptyContent: {
    flexGrow: 1,
    padding: 16,
    paddingBottom: 32,
  },

  header: {
    marginBottom: 16,
    padding: 18,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  eyebrow: {
    fontSize: 13,
    fontWeight: "700",
    color: "#2563eb",
  },

  title: {
    marginTop: 4,
    fontSize: 26,
    fontWeight: "800",
    color: "#0f172a",
  },

  subtitle: {
    marginTop: 7,
    fontSize: 14,
    lineHeight: 20,
    color: "#64748b",
  },

  controls: {
    marginTop: 18,
    gap: 14,
  },

  countCard: {
    padding: 14,
    borderRadius: 12,
    backgroundColor: "#f8fafc",
  },

  countLabel: {
    fontSize: 11,
    fontWeight: "700",
    letterSpacing: 0.7,
    color: "#94a3b8",
  },

  countValue: {
    marginTop: 3,
    fontSize: 25,
    fontWeight: "800",
    color: "#0f172a",
  },

  searchArea: {
    gap: 7,
  },

  searchLabel: {
    fontSize: 13,
    fontWeight: "700",
    color: "#334155",
  },

  searchInput: {
    minHeight: 48,
    paddingHorizontal: 14,
    borderWidth: 1,
    borderColor: "#cbd5e1",
    borderRadius: 12,
    backgroundColor: "#ffffff",
    fontSize: 15,
    color: "#0f172a",
  },

  errorCard: {
    marginBottom: 16,
    padding: 15,
    borderWidth: 1,
    borderColor: "#fecaca",
    borderRadius: 14,
    backgroundColor: "#fef2f2",
  },

  errorText: {
    fontSize: 13,
    lineHeight: 19,
    fontWeight: "600",
    color: "#b91c1c",
  },

  retryText: {
    marginTop: 10,
    fontSize: 13,
    fontWeight: "800",
    color: "#991b1b",
  },

  resultsHeader: {
    marginBottom: 9,
    paddingHorizontal: 2,
  },

  resultsText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#64748b",
  },

  residentCard: {
    minHeight: 88,
    marginBottom: 10,
    padding: 14,
    flexDirection: "row",
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 15,
    backgroundColor: "#ffffff",
  },

  pressed: {
    opacity: 0.65,
  },

  avatar: {
    width: 48,
    height: 48,
    alignItems: "center",
    justifyContent: "center",
    borderRadius: 24,
    backgroundColor: "#dbeafe",
  },

  avatarText: {
    fontSize: 14,
    fontWeight: "800",
    color: "#1d4ed8",
  },

  residentInfo: {
    flex: 1,
    minWidth: 0,
    marginLeft: 13,
  },

  residentName: {
    fontSize: 15,
    fontWeight: "800",
    color: "#0f172a",
  },

  residentMeta: {
    marginTop: 4,
    fontSize: 13,
    color: "#64748b",
  },

  statusBadge: {
    alignSelf: "flex-start",
    marginTop: 7,
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 999,
    backgroundColor: "#eff6ff",
  },

  statusText: {
    fontSize: 10,
    fontWeight: "700",
    color: "#1d4ed8",
  },

  arrow: {
    marginLeft: 8,
    fontSize: 28,
    fontWeight: "300",
    color: "#2563eb",
  },

  stateContainer: {
    flex: 1,
    minHeight: 250,
    alignItems: "center",
    justifyContent: "center",
    padding: 24,
  },

  emptyIcon: {
    fontSize: 36,
  },

  emptyTitle: {
    marginTop: 12,
    fontSize: 16,
    fontWeight: "800",
    color: "#334155",
  },

  stateText: {
    marginTop: 9,
    textAlign: "center",
    fontSize: 13,
    lineHeight: 19,
    color: "#64748b",
  },
});