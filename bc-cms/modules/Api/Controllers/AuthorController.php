<?php
namespace Modules\Api\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\User\Models\User;

class AuthorController extends Controller
{
    /**
     * Public authors - no authentication required
     */
    public function getAuthorsPublic(Request $request)
    {
        try {
            $query = User::where('status', 'publish')
                ->whereNotNull('business_name')
                ->where('business_name', '!=', '');

            // Search by name
            if ($search = $request->query("s")) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                      ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                      ->orWhere('business_name', 'LIKE', '%' . $search . '%');
                });
            }

            // Always return 5 authors per page as requested by frontend
            $perPage = 5;

            // Get paginated results - 5 per page
            $users = $query->paginate($perPage);

            // Get all services to calculate author ratings
            $allServices = $this->getAllServicesWithRatings();

            $rows = [];
            foreach ($users as $user) {
                // Calculate author stats from services data
                $authorStats = $this->calculateAuthorStatsFromServices($user->id, $allServices);

                $rows[] = [
                    'id' => $user->id,
                    'name' => $user->getDisplayName(),
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'business_name' => $user->business_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'bio' => $user->bio,
                    'avatar_url' => $user->avatar_id ? get_file_url($user->avatar_id, 'full') : null,
                    'avatar_thumb_url' => $user->avatar_id ? get_file_url($user->avatar_id) : null,
                    'verify_submit_status' => $user->verify_submit_status,
                    'is_verified' => (bool)$user->is_verified,
                    'created_at' => $user->created_at,
                    'address' => $user->address,
                    'city' => $user->city,
                    'state' => $user->state,
                    'country' => $user->country,
                    // Add calculated ratings and stats from services
                    'average_rating' => $authorStats['average_rating'],
                    'total_reviews' => $authorStats['total_reviews'],
                    'total_services' => $authorStats['total_services'],
                    'review_score' => [
                        'score_total' => $authorStats['average_rating'],
                        'total_review' => $authorStats['total_reviews'],
                    ]
                ];
            }

            return response()->json([
                'data' => $rows,
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total_pages' => $users->lastPage(),
                'status' => 1
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAuthorDetailPublic($id)
    {
        try {
            $author = User::where('status', 'publish')
                         ->where('id', $id)
                         ->whereNotNull('business_name')
                         ->where('business_name', '!=', '')
                         ->first();

            if (!$author) {
                return response()->json([
                    'status' => 0,
                    'message' => "Author not found"
                ], 404);
            }

            // Get all services to calculate author ratings
            $allServices = $this->getAllServicesWithRatings();
            $authorStats = $this->calculateAuthorStatsFromServices($author->id, $allServices);

            $authorData = [
                'id' => $author->id,
                'name' => $author->getDisplayName(),
                'first_name' => $author->first_name,
                'last_name' => $author->last_name,
                'business_name' => $author->business_name,
                'email' => $author->email,
                'phone' => $author->phone,
                'bio' => $author->bio,
                'avatar_url' => $author->avatar_id ? get_file_url($author->avatar_id, 'full') : null,
                'avatar_thumb_url' => $author->avatar_id ? get_file_url($author->avatar_id) : null,
                'verify_submit_status' => $author->verify_submit_status,
                'is_verified' => (bool)$author->is_verified,
                'created_at' => $author->created_at,
                'address' => $author->address,
                'city' => $author->city,
                'state' => $author->state,
                'country' => $author->country,
                // Add calculated ratings and stats from services
                'average_rating' => $authorStats['average_rating'],
                'total_reviews' => $authorStats['total_reviews'],
                'total_services' => $authorStats['total_services'],
                'review_score' => [
                    'score_total' => $authorStats['average_rating'],
                    'total_review' => $authorStats['total_reviews'],
                ]
            ];

            return response()->json([
                'data' => $authorData,
                'status' => 1
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => "Author not found"
            ], 404);
        }
    }

    /**
     * Get all services with their ratings (cached for performance)
     */
    protected function getAllServicesWithRatings()
    {
        // Cache this for 5 minutes to avoid hitting the services API too frequently
        return cache()->remember('all_services_with_ratings', 300, function () {
            $services = [];
            $serviceTypes = [
                'space' => \Modules\Space\Models\Space::class,
                'hotel' => \Modules\Hotel\Models\Hotel::class,
                'tour' => \Modules\Tour\Models\Tour::class,
                'car' => \Modules\Car\Models\Car::class,
                'event' => \Modules\Event\Models\Event::class,
                'boat' => \Modules\Boat\Models\Boat::class,
                'flight' => \Modules\Flight\Models\Flight::class,
            ];

            foreach ($serviceTypes as $type => $model) {
                try {
                    if (class_exists($model)) {
                        $modelServices = $model::where('status', 'publish')
                                             ->get(['id', 'author_id']);

                        foreach ($modelServices as $service) {
                            $reviewData = $service->getScoreReview(); // This should return score_total and total_review
                            $services[] = [
                                'author_id' => $service->author_id,
                                'score_total' => $reviewData['score_total'] ?? 0,
                                'total_review' => $reviewData['total_review'] ?? 0,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return $services;
        });
    }

    /**
     * Calculate author stats from services data
     */
    protected function calculateAuthorStatsFromServices($authorId, $allServices)
    {
        $authorServices = array_filter($allServices, function($service) use ($authorId) {
            return $service['author_id'] == $authorId;
        });

        $totalServices = count($authorServices);

        if ($totalServices === 0) {
            return [
                'average_rating' => 0,
                'total_reviews' => 0,
                'total_services' => 0
            ];
        }

        $totalRating = 0;
        $totalReviews = 0;
        $servicesWithRatings = 0;

        foreach ($authorServices as $service) {
            if ($service['score_total'] > 0) {
                $totalRating += $service['score_total'];
                $totalReviews += $service['total_review'];
                $servicesWithRatings++;
            }
        }

        // Calculate average rating only from services that have ratings
        $averageRating = $servicesWithRatings > 0 ? round($totalRating / $servicesWithRatings, 1) : 0;

        return [
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
            'total_services' => $totalServices
        ];
    }
}
