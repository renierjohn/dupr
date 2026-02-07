const MatchCard = ({ match }) => {
  return (
    <div className="flex flex-col mb-8">
      {match.title && <h4 className="text-center text-sm font-bold mb-2 text-slate-700">{match.title}</h4>}
      <div className="bg-white border border-slate-200 rounded-lg shadow-sm w-full sm:w-64 overflow-hidden min-w-[240px]">
        {match.teams.map((team, idx) => (
          <div key={idx} className={`flex items-center justify-between p-3 ${idx === 0 ? 'border-b border-slate-100' : ''}`}>
            <div className="flex items-center gap-2">
              <div className="w-5 h-5 bg-slate-800 rounded-full flex items-center justify-center text-[10px] text-white">👤</div>
              <span className={`text-sm truncate w-32 ${team.winner ? 'text-winner font-semibold' : 'text-slate-600'}`}>
                {team.name}
              </span>
            </div>
            <div className="flex items-center gap-2">
              {team.winner && <span className="text-winner text-xs">▶</span>}
              <span className={`text-sm font-bold ${team.winner ? 'text-winner' : 'text-slate-400'}`}>{team.score}</span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default MatchCard;
