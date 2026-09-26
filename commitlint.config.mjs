export default {
  rules: {
    'type-empty': [2, 'never'],
    'subject-empty': [2, 'never'],
    'type-enum': [
      2,
      'always',
      ['feat', 'fix', 'perf', 'refactor', 'docs', 'test', 'build', 'ci', 'chore', 'style', 'revert'],
    ],
    'scope-enum': [
      2,
      'always',
      [
        'core',
        'auth',
        'document',
        'helpdesk',
        'intranet',
        'questionnaire',
        'stage',
        'unifolio',
        'shell',
        'front',
        'api',
        'db',
        'docker',
        'deps',
        'ci',
      ],
    ],
  },
};
