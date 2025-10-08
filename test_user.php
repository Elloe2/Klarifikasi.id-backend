echo 'Testing database connection...';
$user = App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password123'),
    'birth_date' => '1990-01-01',
    'education_level' => 'kuliah',
    'institution' => 'Test University'
]);
echo 'User created successfully with ID: ' . $user->id;
