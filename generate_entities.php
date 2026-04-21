<?php

$entities = [
    'User' => [
        'id_user' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'nom' => ['type' => 'string', 'length' => 100],
        'prenom' => ['type' => 'string', 'length' => 100],
        'email' => ['type' => 'string', 'length' => 150, 'unique' => true],
        'mdp' => ['type' => 'string', 'length' => 255],
        'role' => ['type' => 'string', 'length' => 50],
        'num_tel' => ['type' => 'string', 'length' => 20, 'nullable' => true],
        'photo' => ['type' => 'string', 'length' => 500, 'nullable' => true]
    ],
    'Calls' => [
        'id_call' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'id_caller' => ['type' => 'integer'],
        'id_receiver' => ['type' => 'integer'],
        'status' => ['type' => 'string', 'length' => 20, 'nullable' => true],
        'date_appel' => ['type' => 'datetime', 'nullable' => true],
        'duree_secondes' => ['type' => 'integer', 'nullable' => true],
        'caller_ip' => ['type' => 'string', 'length' => 50, 'nullable' => true],
        'caller_port' => ['type' => 'integer', 'nullable' => true]
    ],
    'ChatHistory' => [
        'id' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'id_patient' => ['type' => 'integer'],
        'session_id' => ['type' => 'string', 'length' => 64],
        'role' => ['type' => 'string', 'length' => 16],
        'content' => ['type' => 'text'],
        'created_at' => ['type' => 'datetime', 'nullable' => true]
    ],
    'Formation' => [
        'id' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'title' => ['type' => 'string', 'length' => 255],
        'description' => ['type' => 'text', 'nullable' => true],
        'video_url' => ['type' => 'string', 'length' => 255, 'nullable' => true],
        'category' => ['type' => 'string', 'length' => 100, 'nullable' => true],
        'coach_id' => ['type' => 'integer', 'nullable' => true]
    ],
    'Messages' => [
        'id_message' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'id_expediteur' => ['type' => 'integer'],
        'id_destinataire' => ['type' => 'integer'],
        'contenu' => ['type' => 'text'],
        'date_envoi' => ['type' => 'datetime', 'nullable' => true],
        'lu' => ['type' => 'boolean', 'nullable' => true],
        'modifie' => ['type' => 'boolean', 'nullable' => true],
        'type' => ['type' => 'string', 'length' => 20, 'nullable' => true]
    ],
    'Participation' => [
        'id' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'user_id' => ['type' => 'integer'],
        'formation_id' => ['type' => 'integer'],
        'date_inscription' => ['type' => 'datetime', 'nullable' => true]
    ],
    'Question' => [
        'id' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'quiz_id' => ['type' => 'integer'],
        'question_text' => ['type' => 'text'],
        'points' => ['type' => 'integer', 'nullable' => true]
    ],
    'Quiz' => [
        'id' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'formation_id' => ['type' => 'integer'],
        'title' => ['type' => 'string', 'length' => 255],
        'passing_score' => ['type' => 'integer', 'nullable' => true]
    ],
    'QuizResult' => [
        'id' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'quiz_id' => ['type' => 'integer'],
        'user_id' => ['type' => 'integer'],
        'score' => ['type' => 'integer'],
        'total_points' => ['type' => 'integer'],
        'passed' => ['type' => 'boolean'],
        'completed_at' => ['type' => 'datetime', 'nullable' => true]
    ],
    'Rapport' => [
        'id_rapport' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'id_patient' => ['type' => 'integer'],
        'id_coach' => ['type' => 'integer'],
        'contenu' => ['type' => 'text', 'nullable' => true],
        'recommandations' => ['type' => 'text', 'nullable' => true],
        'nb_seances' => ['type' => 'integer', 'nullable' => true],
        'score_humeur' => ['type' => 'float', 'nullable' => true],
        'periode' => ['type' => 'string', 'length' => 255, 'nullable' => true],
        'date_creation' => ['type' => 'datetime', 'nullable' => true],
        'fichier_pdf' => ['type' => 'string', 'length' => 512, 'nullable' => true]
    ],
    'Reponse' => [
        'id' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'question_id' => ['type' => 'integer'],
        'option_text' => ['type' => 'string', 'length' => 255],
        'is_correct' => ['type' => 'boolean', 'nullable' => true]
    ]
];

$communityEntities = [
    'Category' => [
        'id' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'name' => ['type' => 'string', 'length' => 255]
    ],
    'Post' => [
        'id' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'title' => ['type' => 'string', 'length' => 255],
        'content' => ['type' => 'text'],
        'createdAt' => ['type' => 'datetime'],
        'likes' => ['type' => 'integer', 'nullable' => true],
        'dislikes' => ['type' => 'integer', 'nullable' => true],
        'category' => ['type' => 'Category', 'relation' => 'ManyToOne'],
        'comments' => ['type' => 'Comment', 'relation' => 'OneToMany']
    ],
    'Comment' => [
        'id' => ['type' => 'integer', 'id' => true, 'auto' => true],
        'content' => ['type' => 'text'],
        'createdAt' => ['type' => 'datetime'],
        'post' => ['type' => 'Post', 'relation' => 'ManyToOne']
    ]
];

$all = array_merge($entities, $communityEntities);

if (!is_dir('src/Entity')) {
    mkdir('src/Entity', 0777, true);
}
if (!is_dir('src/Repository')) {
    mkdir('src/Repository', 0777, true);
}

foreach ($all as $name => $fields) {
    $code = "<?php\n\nnamespace App\Entity;\n\nuse App\Repository\\{$name}Repository;\nuse Doctrine\ORM\Mapping as ORM;\n";
    if ($name === 'Post' || $name === 'Category' || $name === 'Comment') {
        $code .= "use Doctrine\Common\Collections\ArrayCollection;\nuse Doctrine\Common\Collections\Collection;\n";
    }
    
    $code .= "\n#[ORM\Entity(repositoryClass: {$name}Repository::class)]\n";
    
    // Add #[ORM\Table] for specific SQL tables
    if (in_array($name, array_keys($entities))) {
        $tableName = strtolower($name);
        if ($name === 'User') $tableName = 'user'; // 'user' is often a reserved keyword but let's keep it as is
        $code .= "#[ORM\Table(name: '`$tableName`')]\n";
    }
    
    $code .= "class $name\n{\n";

    // Constructor for collections
    if ($name === 'Post') {
        $code .= "    public function __construct()\n    {\n        \$this->comments = new ArrayCollection();\n    }\n\n";
    } elseif ($name === 'Category') {
        $code .= "    public function __construct()\n    {\n        \$this->posts = new ArrayCollection();\n    }\n\n";
    }

    $properties = '';
    $getters = '';

    foreach ($fields as $propName => $opts) {
        $type = $opts['type'];
        $phpType = $type;
        if ($type === 'integer') $phpType = 'int';
        if ($type === 'boolean') $phpType = 'bool';
        if ($type === 'text') $phpType = 'string';
        if ($type === 'datetime') $phpType = '\DateTimeInterface';
        
        if (isset($opts['relation'])) {
            if ($opts['relation'] === 'ManyToOne') {
                $properties .= "    #[ORM\ManyToOne(targetEntity: {$type}::class)]\n";
                if ($name === 'Post' && $type === 'Category') {
                    $properties .= "    #[ORM\JoinColumn(nullable: false)]\n";
                }
                if ($name === 'Comment' && $type === 'Post') {
                    $properties .= "    #[ORM\JoinColumn(nullable: false)]\n";
                }
                $phpType = "?$type";
            } elseif ($opts['relation'] === 'OneToMany') {
                $target = $type;
                $mappedBy = strtolower($name);
                $properties .= "    #[ORM\OneToMany(mappedBy: '$mappedBy', targetEntity: {$target}::class)]\n";
                $phpType = "Collection";
            }
        } else {
            $ormType = $type;
            if ($type === 'integer') $ormType = 'integer';
            if ($type === 'boolean') $ormType = 'boolean';
            if ($type === 'datetime') $ormType = 'datetime';
            
            $colOpts = [];
            $colOpts[] = "type: '$ormType'";
            if (isset($opts['length'])) $colOpts[] = "length: " . $opts['length'];
            if (isset($opts['nullable']) && $opts['nullable']) $colOpts[] = "nullable: true";
            if (isset($opts['unique']) && $opts['unique']) $colOpts[] = "unique: true";
            
            if (isset($opts['id'])) {
                $properties .= "    #[ORM\Id]\n";
                if (isset($opts['auto'])) {
                    $properties .= "    #[ORM\GeneratedValue]\n";
                }
            }
            $properties .= "    #[ORM\Column(" . implode(', ', $colOpts) . ")]\n";
            if (!isset($opts['id']) && (!isset($opts['nullable']) || !$opts['nullable'])) {
                if ($phpType !== '\DateTimeInterface') {
                     // nullability inside php typing
                }
            }
        }
        
        $properties .= "    private " . ($phpType === 'Collection' ? '' : '?') . $phpType . " $$propName = null;\n\n";

        // getter/setter
        $capName = ucfirst($propName);
        $getters .= "    public function get$capName(): " . ($phpType === 'Collection' ? 'Collection' : '?'.$phpType) . "\n    {\n        return \$this->$propName;\n    }\n\n";
        
        if ($phpType !== 'Collection') {
            $getters .= "    public function set$capName(" . ($phpType === '\DateTimeInterface' ? '?'.$phpType : '?'.$phpType) . " \$$propName): self\n    {\n        \$this->$propName = \$$propName;\n        return \$this;\n    }\n\n";
        }
    }

    if ($name === 'Category') {
        $properties .= "    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Post::class)]\n";
        $properties .= "    private Collection \$posts;\n\n";
        
        $getters .= "    public function getPosts(): Collection\n    {\n        return \$this->posts;\n    }\n\n";
    }

    $code .= $properties . $getters . "}\n";
    file_put_contents("src/Entity/$name.php", $code);

    // Create barebones repository
    $repoCode = "<?php\n\nnamespace App\Repository;\n\nuse App\Entity\\$name;\nuse Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;\nuse Doctrine\Persistence\ManagerRegistry;\n\nclass {$name}Repository extends ServiceEntityRepository\n{\n    public function __construct(ManagerRegistry \$registry)\n    {\n        parent::__construct(\$registry, {$name}::class);\n    }\n}\n";
    file_put_contents("src/Repository/{$name}Repository.php", $repoCode);
}
echo "Entities generated.\n";
