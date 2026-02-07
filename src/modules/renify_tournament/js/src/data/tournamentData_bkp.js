// src/data/tournamentData.js
export const tournamentData = [
  {
    title: "Quarter-finals",
    matches: [
      { id: 1, teams: [{ name: "Charlie Solamillo & J...", score: 15, winner: true }, { name: "Argie Villacampa & ...", score: 13 }] },
      { id: 2, teams: [{ name: "Charlie Beltran & Pri...", score: 15, winner: true }, { name: "Shmirton & Keith Ry...", score: 10 }] },
      { id: 3, teams: [{ name: "Jake roll & Jaydovhe...", score: 13 }, { name: "Pj-OTS & George", score: 15, winner: true }] },
      { id: 4, teams: [{ name: "Joe Mark & Jonathan...", score: 2 }, { name: "Wenchester Limpah...", score: 15, winner: true }] },
    ],
  },
  {
    title: "Semi-finals",
    matches: [
      { id: 5, teams: [{ name: "Charlie Solamillo & J...", score: 11 }, { name: "Charlie Beltran & Pri...", score: 15, winner: true }] },
      { id: 6, teams: [{ name: "Pj-OTS & George", score: 7 }, { name: "Wenchester Limpah...", score: 15, winner: true }] },
    ],
  },
  {
    title: "Final",
    matches: [
      { id: 7, title: "1st place", teams: [{ name: "Charlie Beltran & Pri...", score: 9 }, { name: "Wenchester Limpah...", score: 15, winner: true }] },
      { id: 8, title: "3rd place", teams: [{ name: "Pj-OTS & George", score: 15, winner: true }, { name: "Charlie Solamillo & J...", score: 5 }] },
    ],
  },
];
