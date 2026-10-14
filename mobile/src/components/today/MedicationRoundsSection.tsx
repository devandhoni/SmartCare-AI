import React from "react";
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from "react-native";

import type {
  TodayMedicationItem,
  TodayMedicationRound,
} from "../../types/today";

interface MedicationRoundsSectionProps {
  rounds: {
    AM: TodayMedicationRound;
    PM: TodayMedicationRound;
    NIGHT: TodayMedicationRound;
  };
  onOpenMedication: () => void;
}

export default function MedicationRoundsSection({
  rounds,
  onOpenMedication,
}: MedicationRoundsSectionProps) {
  return (
    <View style={styles.panel}>
      <View style={styles.panelHeader}>
        <View style={styles.headerText}>
          <Text style={styles.panelTitle}>
            Medication Rounds
          </Text>

          <Text style={styles.panelDescription}>
            Today's medication schedule for active residents.
          </Text>
        </View>

        <Pressable onPress={onOpenMedication}>
          <Text style={styles.openLink}>
            Medication ›
          </Text>
        </Pressable>
      </View>

      <View style={styles.content}>
        <MedicationRoundCard
          title="AM"
          meal="Breakfast"
          round={rounds.AM}
          onOpenMedication={onOpenMedication}
        />

        <MedicationRoundCard
          title="PM"
          meal="Lunch"
          round={rounds.PM}
          onOpenMedication={onOpenMedication}
        />

        <MedicationRoundCard
          title="Night"
          meal="Dinner"
          round={rounds.NIGHT}
          onOpenMedication={onOpenMedication}
        />
      </View>
    </View>
  );
}

function MedicationRoundCard({
  title,
  meal,
  round,
  onOpenMedication,
}: {
  title: string;
  meal: string;
  round: TodayMedicationRound;
  onOpenMedication: () => void;
}) {
  const hasCriticalAttention =
    round.overdue > 0 ||
    round.exceptions > 0;

  const hasMealAttention =
    round.meals_pending > 0;

  return (
    <View
      style={[
        styles.roundCard,
        hasCriticalAttention &&
          styles.roundCardCritical,
        !hasCriticalAttention &&
          hasMealAttention &&
          styles.roundCardAttention,
      ]}
    >
      <View style={styles.roundHeader}>
        <View>
          <Text style={styles.roundTitle}>
            {title}
          </Text>

          <Text style={styles.mealText}>
            {meal}
          </Text>
        </View>

        <View style={styles.progressBadge}>
          <Text style={styles.progressText}>
            {round.completed}/{round.total}
          </Text>
        </View>
      </View>

      {hasCriticalAttention ||
      hasMealAttention ? (
        <View style={styles.badges}>
          {round.overdue > 0 ? (
            <StatusBadge
              text={`${round.overdue} overdue`}
              type="critical"
            />
          ) : null}

          {round.exceptions > 0 ? (
            <StatusBadge
              text={`${round.exceptions} exception${
                round.exceptions === 1 ? "" : "s"
              }`}
              type="critical"
            />
          ) : null}

          {round.meals_pending > 0 ? (
            <StatusBadge
              text={`${round.meals_pending} meal pending`}
              type="attention"
            />
          ) : null}
        </View>
      ) : null}

      {round.items.length === 0 ? (
        <Text style={styles.emptyText}>
          No medication scheduled.
        </Text>
      ) : (
        <View style={styles.items}>
          {round.items
            .slice(0, 5)
            .map((item) => (
              <MedicationItem
                key={item.resident_medication_id}
                item={item}
                onOpenMedication={
                  onOpenMedication
                }
              />
            ))}

          {round.items.length > 5 ? (
            <Text style={styles.moreText}>
              +{round.items.length - 5} more scheduled
            </Text>
          ) : null}
        </View>
      )}
    </View>
  );
}

function MedicationItem({
  item,
  onOpenMedication,
}: {
  item: TodayMedicationItem;
  onOpenMedication: () => void;
}) {
  return (
    <View style={styles.medicationItem}>
      <View style={styles.itemHeader}>
        <View style={styles.itemText}>
          <Text
            style={styles.residentName}
            numberOfLines={1}
          >
            {item.resident || "Resident"}
          </Text>

          <Text
            style={styles.medicineName}
            numberOfLines={1}
          >
            {item.medicine || "Medication"}
            {item.scheduled_time
              ? ` • ${formatMedicationTime(
                  item.scheduled_time
                )}`
              : ""}
          </Text>
        </View>

        <MedicationStatus item={item} />
      </View>

      {item.remarks ? (
        <Text style={styles.remarks}>
          {item.remarks}
        </Text>
      ) : null}

      <Pressable onPress={onOpenMedication}>
        <Text style={styles.itemLink}>
          Open Medication ›
        </Text>
      </Pressable>
    </View>
  );
}

function MedicationStatus({
  item,
}: {
  item: TodayMedicationItem;
}) {
  const status = String(
    item.status || "PENDING"
  ).toUpperCase();

  let text = "Pending";
  let type:
    | "normal"
    | "attention"
    | "critical"
    | "success" = "attention";

  if (
    item.overdue &&
    ["PENDING", "DELAYED"].includes(status)
  ) {
    text = "Overdue";
    type = "critical";
  } else if (
    status === "COMPLETED" &&
    item.meal_type &&
    !item.meal_confirmed
  ) {
    text = `Awaiting ${item.meal_type}`;
    type = "attention";
  } else if (status === "COMPLETED") {
    text = "Complete";
    type = "success";
  } else if (status === "DELAYED") {
    text = "Delayed";
    type = "attention";
  } else if (
    [
      "HELD",
      "REFUSED",
      "UNAVAILABLE",
      "MISSED",
    ].includes(status)
  ) {
    text =
      status.charAt(0) +
      status.slice(1).toLowerCase();

    type = "critical";
  }

  return (
    <StatusBadge
      text={text}
      type={type}
    />
  );
}

function StatusBadge({
  text,
  type,
}: {
  text: string;
  type:
    | "normal"
    | "attention"
    | "critical"
    | "success";
}) {
  return (
    <View
      style={[
        styles.statusBadge,
        type === "attention" &&
          styles.statusAttention,
        type === "critical" &&
          styles.statusCritical,
        type === "success" &&
          styles.statusSuccess,
        type === "normal" &&
          styles.statusNormal,
      ]}
    >
      <Text
        style={[
          styles.statusText,
          type === "attention" &&
            styles.statusTextAttention,
          type === "critical" &&
            styles.statusTextCritical,
          type === "success" &&
            styles.statusTextSuccess,
          type === "normal" &&
            styles.statusTextNormal,
        ]}
      >
        {text}
      </Text>
    </View>
  );
}

function formatMedicationTime(
  value: string
): string {
  const parts = String(value).split(":");

  if (parts.length < 2) {
    return value;
  }

  const date = new Date();

  date.setHours(
    Number(parts[0]),
    Number(parts[1]),
    Number(parts[2] || 0),
    0
  );

  return date.toLocaleTimeString(undefined, {
    hour: "numeric",
    minute: "2-digit",
  });
}

const styles = StyleSheet.create({
  panel: {
    marginTop: 12,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 16,
    backgroundColor: "#ffffff",
  },

  panelHeader: {
    padding: 16,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: "#e2e8f0",
  },

  headerText: {
    marginBottom: 10,
  },

  panelTitle: {
    fontSize: 17,
    fontWeight: "700",
    color: "#0f172a",
  },

  panelDescription: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 19,
    color: "#64748b",
  },

  openLink: {
    fontSize: 14,
    fontWeight: "700",
    color: "#2563eb",
  },

  content: {
    padding: 14,
  },

  roundCard: {
    marginBottom: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 14,
    backgroundColor: "#f8fafc",
  },

  roundCardCritical: {
    borderColor: "#fecaca",
    backgroundColor: "#fef2f2",
  },

  roundCardAttention: {
    borderColor: "#fde68a",
    backgroundColor: "#fffbeb",
  },

  roundHeader: {
    flexDirection: "row",
    alignItems: "flex-start",
    justifyContent: "space-between",
  },

  roundTitle: {
    fontSize: 16,
    fontWeight: "700",
    color: "#0f172a",
  },

  mealText: {
    marginTop: 2,
    fontSize: 12,
    color: "#64748b",
  },

  progressBadge: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 999,
    backgroundColor: "#ffffff",
  },

  progressText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#475569",
  },

  badges: {
    flexDirection: "row",
    flexWrap: "wrap",
    marginTop: 10,
    gap: 6,
  },

  items: {
    marginTop: 12,
  },

  medicationItem: {
    marginBottom: 8,
    padding: 12,
    borderWidth: 1,
    borderColor: "#e2e8f0",
    borderRadius: 11,
    backgroundColor: "#ffffff",
  },

  itemHeader: {
    flexDirection: "row",
    alignItems: "flex-start",
    justifyContent: "space-between",
  },

  itemText: {
    flex: 1,
    marginRight: 8,
  },

  residentName: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1e293b",
  },

  medicineName: {
    marginTop: 4,
    fontSize: 12,
    color: "#64748b",
  },

  remarks: {
    marginTop: 8,
    fontSize: 12,
    lineHeight: 18,
    color: "#64748b",
  },

  itemLink: {
    marginTop: 10,
    fontSize: 12,
    fontWeight: "700",
    color: "#2563eb",
  },

  emptyText: {
    marginTop: 18,
    marginBottom: 6,
    fontSize: 13,
    color: "#94a3b8",
  },

  moreText: {
    marginTop: 2,
    fontSize: 12,
    fontWeight: "600",
    color: "#64748b",
  },

  statusBadge: {
    alignSelf: "flex-start",
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 999,
  },

  statusNormal: {
    backgroundColor: "#f1f5f9",
  },

  statusAttention: {
    backgroundColor: "#fef3c7",
  },

  statusCritical: {
    backgroundColor: "#fee2e2",
  },

  statusSuccess: {
    backgroundColor: "#d1fae5",
  },

  statusText: {
    fontSize: 10,
    fontWeight: "700",
  },

  statusTextNormal: {
    color: "#475569",
  },

  statusTextAttention: {
    color: "#92400e",
  },

  statusTextCritical: {
    color: "#b91c1c",
  },

  statusTextSuccess: {
    color: "#047857",
  },
});