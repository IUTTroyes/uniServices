export function libelleOrdreMission(mission) {
  if (mission.typeOm === 'permanent') return 'Déclaré couvert par l’OM permanent';
  const label = mission.typeOm === 'ponctuel' ? 'OM ponctuel' : 'OM';
  return label + ' · ' + mission.ordreMission;
}
