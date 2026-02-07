import { tournamentData } from "../data/tournamentData";
import MatchCard from "./MatchCard";

const Bracket = () => {
  return (
    <div className="flex justify-start md:justify-center items-start gap-6 md:gap-12 overflow-x-auto p-4 md:p-8">
      {tournamentData.map((round, roundIndex) => (
        <div key={roundIndex} className="flex flex-col items-center min-w-[250px]">
          {/* Round Header */}
          <h3 className="text-slate-400 uppercase text-[11px] tracking-[0.2em] font-bold mb-16">
            {round.title}
          </h3>

          {/* Matches Container */}
          <div className="flex flex-col justify-around h-full w-full relative">
            {round.matches.map((match, matchIndex) => (
              <div key={match.id} className="relative flex items-center justify-center">
                <MatchCard match={match} />

                {/* SVG Connectors (Optional refinement) */}
                {roundIndex < tournamentData.length - 1 && (
                  <div className="absolute -right-12 w-12 h-px bg-slate-200" />
                )}
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
};

export default Bracket;
